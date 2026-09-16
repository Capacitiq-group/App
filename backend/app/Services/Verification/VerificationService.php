<?php

namespace App\Services\Verification;

use App\Enums\User\UserVerifyStatusEnum;
use App\Enums\Verification\VerificationApplicantTypeEnum;
use App\Enums\Verification\VerificationBillingCycleEnum;
use App\Enums\Verification\VerificationEventTypeEnum;
use App\Enums\Verification\VerificationHoldStatusEnum;
use App\Enums\Verification\VerificationStatusEnum;
use App\Exceptions\http\BusinessException;
use App\Exceptions\http\ForbiddenException;
use App\Models\User;
use App\Models\VerificationApplication;
use App\Repositories\VerificationApplicationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerificationService
{
    /**
     * Didit's exact, case-sensitive status strings that end a hold one way
     * or the other. Everything else (Not Started, In Progress, Resubmitted,
     * Expired, Kyc Expired) is left alone — no hold action, no status change
     * beyond logging, since none of those are a final decision.
     */
    private const DIDIT_APPROVED = 'Approved';
    private const DIDIT_DECLINED = 'Declined';
    private const DIDIT_IN_REVIEW = 'In Review';
    private const DIDIT_ABANDONED = 'Abandoned';

    public function __construct(
        private readonly VerificationApplicationRepository $applications,
        private readonly PaystackPreauthService $paystack,
        private readonly DiditService $didit,
    ) {}

    /**
     * Basic gate before letting someone start an application at all — the
     * "60+ day account, 10+ public posts, good standing" eligibility from
     * the doc. Deliberately separate from SpaceService's host-eligibility
     * check even though it looks similar; these are two independent gates
     * with their own numbers, not the same rule reused.
     *
     * @throws ForbiddenException
     */
    public function assertCanApply(User $user): void
    {
        if ($user->created_at->diffInDays(now()) < 60) {
            throw new ForbiddenException('Your account must be at least 60 days old to apply for verification.');
        }

        $publicPostCount = $user->posts()->where('audience', 'public')->count();

        if ($publicPostCount < 10) {
            throw new ForbiddenException('You need at least 10 public posts to apply for verification.');
        }

        if ($user->isBanned()) {
            throw new ForbiddenException('Your account is not in good standing.');
        }

        if ($this->applications->hasOpenApplication($user->id)) {
            throw new BusinessException('You already have a verification application in progress.');
        }
    }

    /**
     * Start an application: place the Paystack hold, build the Didit URL,
     * and create the applicant-type-specific detail row — all in one
     * transaction, since a hold with no detail row (or vice versa) is a
     * broken application. $details' shape depends on $applicantType: pass
     * the fillable fields for VerificationIndividualDetail,
     * VerificationBusinessDetail, or VerificationPoliticalDetail respectively.
     *
     * @param  array<string, mixed>  $details
     * @return array{application: VerificationApplication, didit_url: string, paystack_authorization_url: string}
     *
     * @throws ForbiddenException|BusinessException
     */
    public function submit(User $user, VerificationApplicantTypeEnum $applicantType, VerificationBillingCycleEnum $billingCycle, array $details, string $callbackUrl): array
    {
        $this->assertCanApply($user);

        return DB::transaction(function () use ($user, $applicantType, $billingCycle, $details, $callbackUrl) {
            $application = $this->applications->create([
                'user_id' => $user->id,
                'applicant_type' => $applicantType->value,
                'billing_cycle' => $billingCycle->value,
                'status' => VerificationStatusEnum::IN_PROGRESS->value,
                'submitted_at' => now(),
            ]);

            match ($applicantType) {
                VerificationApplicantTypeEnum::INDIVIDUAL => $application->individualDetails()->create($details),
                VerificationApplicantTypeEnum::BUSINESS => $application->businessDetails()->create($details),
                VerificationApplicantTypeEnum::POLITICAL_ENTITY => $application->politicalDetails()->create($details),
            };

            $hold = $this->paystack->initializeHold(
                email: $user->email,
                amountCents: $billingCycle->feeCents(),
                callbackUrl: $callbackUrl,
                reference: 'verify_'.$application->uuid,
            );

            $application->update([
                'paystack_reference' => $hold['reference'],
                'hold_status' => VerificationHoldStatusEnum::HELD->value,
                'hold_expires_at' => now()->addDays((int) config('paystack.verification.hold_expire_days')),
            ]);

            $this->logEvent($application, VerificationEventTypeEnum::HOLD_PLACED, null, [
                'reference' => $hold['reference'],
            ]);

            $diditUrl = $this->didit->buildVerificationUrl($applicantType, $application->uuid);

            $this->logEvent($application, VerificationEventTypeEnum::SUBMITTED, null, [
                'applicant_type' => $applicantType->value,
            ]);

            return [
                'application' => $application->refresh(),
                'didit_url' => $diditUrl,
                'paystack_authorization_url' => $hold['authorization_url'],
            ];
        });
    }

    /**
     * Process a Didit webhook payload. This is the authoritative signal —
     * never the browser redirect — per the platform rules doc.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleDiditWebhook(array $payload): void
    {
        $sessionId = $payload['session_id'] ?? null;
        $vendorData = $payload['vendor_data'] ?? null;
        $status = $payload['status'] ?? null;

        if ($vendorData === null) {
            Log::warning('Didit webhook missing vendor_data — cannot match to an application', ['payload' => $payload]);

            return;
        }

        $application = $this->applications->findByUuid((string) $vendorData);

        if ($application === null) {
            Log::warning('Didit webhook vendor_data did not match any application', ['vendor_data' => $vendorData]);

            return;
        }

        if ($application->status->isTerminal()) {
            // Already decided — a duplicate/retried webhook. Log and no-op
            // rather than re-running capture/release against a resolved application.
            $this->logEvent($application, VerificationEventTypeEnum::DIDIT_WEBHOOK, null, [
                'note' => 'ignored — application already in a terminal state',
                'incoming_status' => $status,
            ]);

            return;
        }

        if ($application->didit_session_id === null && $sessionId !== null) {
            $application->update(['didit_session_id' => $sessionId]);
        }

        $this->logEvent($application, VerificationEventTypeEnum::DIDIT_WEBHOOK, null, ['status' => $status]);

        match ($status) {
            self::DIDIT_APPROVED => $this->approve($application, $payload, manual: false),
            self::DIDIT_DECLINED, self::DIDIT_ABANDONED => $this->reject($application, 'Didit: '.$status, manual: false),
            self::DIDIT_IN_REVIEW => $this->flagForReview($application),
            default => null, // Not Started / In Progress / Resubmitted / Expired / Kyc Expired — no action.
        };
    }

    /**
     * Manual admin approval — for anything sitting in under_review.
     *
     * @throws BusinessException If the hold already expired (see reconcileStuckApplications()).
     */
    public function manuallyApprove(VerificationApplication $application, User $admin): void
    {
        if ($application->holdHasExpired()) {
            throw new BusinessException('The payment hold on this application has expired — re-request payment before approving.');
        }

        $this->approve($application, [], manual: true, admin: $admin);
    }

    /**
     * Manual admin rejection.
     */
    public function manuallyReject(VerificationApplication $application, User $admin, string $reason): void
    {
        $this->reject($application, $reason, manual: true, admin: $admin);
    }

    /**
     * React to Paystack confirming a capture that VerificationService::approve()
     * already performed synchronously — just logs the confirmation for the
     * audit trail. No-op if the reference doesn't match a verification application
     * (it may belong to a different paid feature, e.g. a tip).
     */
    public function handleCaptureWebhookConfirmation(string $paystackReference): void
    {
        $application = $this->applications->query()->where('paystack_reference', $paystackReference)->first();

        if ($application === null) {
            return;
        }

        $this->logEvent($application, VerificationEventTypeEnum::HOLD_CAPTURED, null, ['source' => 'webhook_confirmation']);
    }

    /**
     * The rare async edge case: a capture() call didn't get a definitive
     * response, and Paystack later confirmed it actually failed — after the
     * application was already marked VERIFIED. Flags for manual staff
     * attention rather than automatically revoking a granted badge.
     */
    public function handleCaptureFailedAfterApproval(string $paystackReference): void
    {
        $application = $this->applications->query()->where('paystack_reference', $paystackReference)->first();

        if ($application === null) {
            return;
        }

        Log::critical('Paystack capture failed after verification was already marked approved', [
            'verification_application_id' => $application->id,
            'paystack_reference' => $paystackReference,
        ]);

        $this->flagForReview($application, 'Payment capture failed asynchronously after approval — needs manual resolution before the badge should be considered valid.');
    }

    /**
     * Approve: capture the hold, flip the badge on, mark verified.
     */
    private function approve(VerificationApplication $application, array $diditPayload, bool $manual, ?User $admin = null): void
    {
        DB::transaction(function () use ($application, $diditPayload, $manual, $admin) {
            if ($application->businessDetails !== null) {
                $this->updateBusinessMatchFromDecision($application, $diditPayload);

                // Fail closed: a CIPC-registered business that hasn't
                // positively confirmed the officer match does NOT get
                // auto-approved, even though Didit itself said "Approved" —
                // Didit approving the identity checks and Synkra granting
                // account control are two different questions (see
                // VerificationBusinessDetail::qualifiesForAccountControl()).
                $application->businessDetails->refresh();

                if (! $application->businessDetails->qualifiesForAccountControl()) {
                    $this->flagForReview($application, 'Didit approved the identity checks, but the CIPC officer match could not be confirmed automatically — needs manual confirmation before granting account control.');

                    return;
                }
            }

            $this->paystack->capture($application->paystack_reference);

            $this->startRecurringSubscription($application);

            $application->update([
                'status' => VerificationStatusEnum::VERIFIED->value,
                'hold_status' => VerificationHoldStatusEnum::CAPTURED->value,
                'decided_manually' => $manual,
                'reviewed_by' => $admin?->id,
                'decided_at' => now(),
            ]);

            $this->applyBadge($application);

            $this->logEvent(
                $application,
                $manual ? VerificationEventTypeEnum::MANUALLY_APPROVED : VerificationEventTypeEnum::AUTO_APPROVED,
                $admin?->id,
            );
            $this->logEvent($application, VerificationEventTypeEnum::HOLD_CAPTURED, $admin?->id);
        });
    }

    /**
     * Set up the ongoing monthly/annual charge after the first payment
     * succeeds. Failure here is logged, not thrown — the person already
     * paid their first cycle and passed identity checks, so the badge still
     * gets granted; a missing subscription just means staff need to set up
     * recurring billing manually for that account, not that the whole
     * approval should fail.
     */
    private function startRecurringSubscription(VerificationApplication $application): void
    {
        $planCode = $application->billing_cycle->paystackPlanCode();

        if ($planCode === '') {
            Log::warning('No Paystack plan code configured for billing cycle — skipping subscription setup', [
                'verification_application_id' => $application->id,
                'billing_cycle' => $application->billing_cycle->value,
            ]);

            return;
        }

        try {
            $subscription = $this->paystack->createSubscription(
                email: $application->user->email,
                planCode: $planCode,
                authorizationReference: $application->paystack_reference,
            );

            $application->update(['paystack_subscription_code' => $subscription['subscription_code']]);
        } catch (BusinessException $e) {
            Log::error('Failed to create recurring verification subscription', [
                'verification_application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reject: release the hold, nothing charged.
     */
    private function reject(VerificationApplication $application, string $reason, bool $manual, ?User $admin = null): void
    {
        DB::transaction(function () use ($application, $reason, $manual, $admin) {
            $this->paystack->release($application->paystack_reference);

            $application->update([
                'status' => VerificationStatusEnum::FAILED->value,
                'hold_status' => VerificationHoldStatusEnum::RELEASED->value,
                'decided_manually' => $manual,
                'reviewed_by' => $admin?->id,
                'decision_reason' => $reason,
                'decided_at' => now(),
            ]);

            $this->logEvent(
                $application,
                $manual ? VerificationEventTypeEnum::MANUALLY_REJECTED : VerificationEventTypeEnum::AUTO_REJECTED,
                $admin?->id,
                ['reason' => $reason],
            );
            $this->logEvent($application, VerificationEventTypeEnum::HOLD_RELEASED, $admin?->id);
        });
    }

    /**
     * Move an application into the admin review queue. Hold stays untouched.
     */
    private function flagForReview(VerificationApplication $application, ?string $note = null): void
    {
        $application->update(['status' => VerificationStatusEnum::UNDER_REVIEW->value]);

        $this->logEvent($application, VerificationEventTypeEnum::FLAGGED_FOR_REVIEW, null, array_filter(['note' => $note]));
    }

    /**
     * Attempt to extract the CIPC-officer-match result from a Didit
     * decision payload.
     *
     * HONESTY NOTE: the exact JSON path Didit uses for a KYB decision's
     * matched-officer result isn't confirmed against a real payload here —
     * this checks a couple of plausible shapes and otherwise leaves
     * matched_cipc_officer null (which qualifiesForAccountControl() treats
     * as "not confirmed", i.e. still routes to manual review). Replace the
     * key paths below once you've inspected a real Didit KYB decision
     * response in the sandbox.
     */
    private function updateBusinessMatchFromDecision(VerificationApplication $application, array $diditPayload): void
    {
        $decision = $diditPayload['decision'] ?? null;

        if ($decision === null && $application->didit_session_id !== null) {
            try {
                $decision = $this->didit->fetchDecision($application->didit_session_id);
            } catch (BusinessException) {
                $decision = null;
            }
        }

        $kyb = $decision['kyb'] ?? $decision['business'] ?? null;
        $matchedOfficer = $kyb['matched_officer'] ?? $kyb['matched_key_person'] ?? null;

        if ($matchedOfficer === null) {
            return; // stays null — treated as unconfirmed, not as "no match".
        }

        $application->businessDetails?->update([
            'matched_cipc_officer' => (bool) ($matchedOfficer['matched'] ?? true),
            'matched_officer_name' => $matchedOfficer['name'] ?? null,
        ]);
    }

    /**
     * Set the profile-facing badge fields once an application is verified.
     */
    private function applyBadge(VerificationApplication $application): void
    {
        $label = match (true) {
            $application->applicant_type === VerificationApplicantTypeEnum::BUSINESS => 'Verified Business',
            $application->applicant_type === VerificationApplicantTypeEnum::POLITICAL_ENTITY => 'Verified Organization',
            $application->individualDetails !== null => $application->individualDetails->verifying_as->badgeLabel(),
            default => 'Verified',
        };

        $depth = match (true) {
            $application->applicant_type === VerificationApplicantTypeEnum::BUSINESS
                && $application->businessDetails?->is_cipc_registered => 'registry',
            default => 'identity_only',
        };

        $application->user->update([
            'verify' => UserVerifyStatusEnum::VERIFIED->value,
            'verify_badge_label' => $label,
            'verify_depth' => $depth,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function logEvent(VerificationApplication $application, VerificationEventTypeEnum $type, ?int $actorId, array $metadata = []): void
    {
        $application->events()->create([
            'event_type' => $type->value,
            'actor_id' => $actorId,
            'metadata' => $metadata ?: null,
        ]);
    }
}
