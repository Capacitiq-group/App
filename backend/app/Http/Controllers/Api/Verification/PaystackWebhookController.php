<?php

namespace App\Http\Controllers\Api\Verification;

use App\Enums\Verification\VerificationEventTypeEnum;
use App\Enums\Verification\VerificationStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\VerificationApplication;
use App\Repositories\VerificationApplicationRepository;
use App\Services\Verification\PaystackPreauthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private readonly PaystackPreauthService $paystack,
        private readonly VerificationApplicationRepository $applications,
    ) {}

    /**
     * Scope note: this only handles the preauthorization events relevant to
     * Verification's own R79 hold. A general Paystack webhook handler
     * covering charge.dispute.create / the 48-hour dispute response window
     * from the platform rules doc applies to every Paystack transaction on
     * the platform, not just Verification — that's a separate, broader
     * payments concern to build once other paid features exist.
     */
    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('x-paystack-signature');

        if (! $this->paystack->verifyWebhookSignature($request->getContent(), $signature)) {
            Log::warning('Paystack webhook signature verification failed', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $reference = $request->input('data.reference');

        if ($reference === null) {
            return response()->json(['received' => true]);
        }

        $application = $this->applications->query()->where('paystack_reference', $reference)->first();

        if ($application === null) {
            // Not every preauthorization on the account is necessarily a
            // Verification hold once other paid features exist — ignore
            // silently rather than logging noise for those.
            return response()->json(['received' => true]);
        }

        match ($event) {
            'preauthorization.capture.success' => $this->confirmCapture($application),
            'preauthorization.capture.failed' => $this->flagCaptureFailure($application),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    /**
     * The common path already marks an application VERIFIED synchronously
     * right after a successful capture() call (see VerificationService::approve()).
     * This just logs the webhook confirmation for the audit trail.
     */
    private function confirmCapture(VerificationApplication $application): void
    {
        $application->events()->create([
            'event_type' => VerificationEventTypeEnum::HOLD_CAPTURED->value,
            'metadata' => ['source' => 'webhook_confirmation'],
        ]);
    }

    /**
     * The rare async edge case the doc warns about: our synchronous
     * capture() call didn't get a definitive response, Paystack polled the
     * processor afterward, and it came back failed — after we'd already
     * marked the application VERIFIED. Rather than automatically revoking a
     * granted badge (a bigger, riskier automatic action), this flags it
     * loudly for manual staff attention.
     */
    private function flagCaptureFailure(VerificationApplication $application): void
    {
        Log::critical('Paystack capture failed after verification was already marked approved', [
            'verification_application_id' => $application->id,
            'paystack_reference' => $application->paystack_reference,
        ]);

        $application->update(['status' => VerificationStatusEnum::UNDER_REVIEW->value]);

        $application->events()->create([
            'event_type' => VerificationEventTypeEnum::FLAGGED_FOR_REVIEW->value,
            'metadata' => ['note' => 'Payment capture failed asynchronously after approval — needs manual resolution before the badge should be considered valid.'],
        ]);
    }
}
