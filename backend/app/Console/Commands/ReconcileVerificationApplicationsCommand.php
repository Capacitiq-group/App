<?php

namespace App\Console\Commands;

use App\Enums\Verification\VerificationEventTypeEnum;
use App\Enums\Verification\VerificationHoldStatusEnum;
use App\Enums\Verification\VerificationStatusEnum;
use App\Repositories\VerificationApplicationRepository;
use App\Services\Verification\PaystackPreauthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileVerificationApplicationsCommand extends Command
{
    protected $signature = 'verification:reconcile';

    protected $description = 'Catch verification applications whose Paystack hold expired or that never got a Didit webhook, per the platform rules doc\'s explicit reconciliation requirement.';

    public function __construct(
        private readonly VerificationApplicationRepository $applications,
        private readonly PaystackPreauthService $paystack,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->reconcileExpiredHolds();
        $this->flagStuckInProgress();

        return self::SUCCESS;
    }

    /**
     * Applications whose hold has passed its expiry with no decision.
     * Paystack itself auto-releases at expiry (expire_action=release in
     * config/paystack.php) — this doesn't call release() again, it confirms
     * that happened and moves the application to extra_review_needed so
     * payment gets re-requested, per Section 4.4, step 7 of the doc.
     */
    private function reconcileExpiredHolds(): void
    {
        $expired = $this->applications->withExpiredHoldsAwaitingDecision();

        foreach ($expired as $application) {
            try {
                $status = $this->paystack->fetchStatus($application->paystack_reference);
            } catch (\Throwable $e) {
                Log::warning('Could not fetch Paystack status during verification reconciliation', [
                    'verification_application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if (($status['status'] ?? null) !== 'released') {
                // Hold is still active on Paystack's side despite our
                // expected expiry — don't touch it, avoid racing an
                // in-flight decision.
                continue;
            }

            $application->update([
                'status' => VerificationStatusEnum::EXTRA_REVIEW_NEEDED->value,
                'hold_status' => VerificationHoldStatusEnum::EXPIRED->value,
            ]);

            $application->events()->create([
                'event_type' => VerificationEventTypeEnum::HOLD_EXPIRED->value,
                'metadata' => ['note' => 'Confirmed released on Paystack via reconciliation — no decision was reached in time.'],
            ]);
        }

        if ($expired->isNotEmpty()) {
            $this->info("Reconciled {$expired->count()} expired verification hold(s).");
        }
    }

    /**
     * Applications sitting in_progress well past a reasonable window with no
     * webhook ever received — the doc's specific worry about Didit webhook
     * delivery not being guaranteed. This only logs; it doesn't change
     * status, since "no webhook yet" isn't the same as "known to have failed".
     */
    private function flagStuckInProgress(): void
    {
        $stuck = $this->applications->query()
            ->where('status', VerificationStatusEnum::IN_PROGRESS->value)
            ->whereNull('didit_session_id')
            ->where('submitted_at', '<=', now()->subHours(24))
            ->get();

        foreach ($stuck as $application) {
            Log::warning('Verification application stuck in_progress with no Didit webhook received', [
                'verification_application_id' => $application->id,
                'submitted_at' => $application->submitted_at,
            ]);
        }
    }
}
