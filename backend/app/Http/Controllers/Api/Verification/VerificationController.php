<?php

namespace App\Http\Controllers\Api\Verification;

use App\Enums\Verification\VerificationApplicantTypeEnum;
use App\Enums\Verification\VerificationBillingCycleEnum;
use App\Enums\Verification\VerificationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Verification\CreateVerificationApplicationRequest;
use App\Http\Resources\Api\Verification\VerificationApplicationResource;
use App\Http\Response\ApiResponse;
use App\Repositories\VerificationApplicationRepository;
use App\Services\Verification\DiditService;
use App\Services\Verification\VerificationService;
use App\Traits\HasAuthUser;
use Illuminate\Http\JsonResponse;

class VerificationController extends Controller
{
    use HasAuthUser;

    public function __construct(
        private readonly VerificationService $verificationService,
        private readonly VerificationApplicationRepository $applications,
        private readonly DiditService $didit,
    ) {}

    /**
     * Submit a verification application. Returns both redirect URLs — the
     * client sends the user to paystack_authorization_url first (card entry,
     * places the hold), then to didit_url once Paystack redirects back.
     */
    public function submit(CreateVerificationApplicationRequest $request): JsonResponse
    {
        $applicantType = VerificationApplicantTypeEnum::from($request->validated('applicant_type'));
        $billingCycle = VerificationBillingCycleEnum::from($request->validated('billing_cycle'));

        $result = $this->verificationService->submit(
            user: $this->guard()->user(),
            applicantType: $applicantType,
            billingCycle: $billingCycle,
            details: $request->detailFields(),
            callbackUrl: (string) $request->validated('callback_url', config('app.frontend_url').'/verification/callback'),
        );

        $resource = new VerificationApplicationResource($result['application']);
        $resource->diditUrl = $result['didit_url'];

        return ApiResponse::created(
            data: [
                'application' => $resource,
                'paystack_authorization_url' => $result['paystack_authorization_url'],
            ],
            message: 'Verification application submitted'
        );
    }

    /**
     * Get the authenticated user's most recent verification application, if any.
     * Used by the settings screen and by the post-Paystack-redirect callback
     * page to fetch the Didit URL to continue to.
     */
    public function status(): JsonResponse
    {
        $application = $this->applications->query()
            ->where('user_id', $this->guard()->id())
            ->latest('submitted_at')
            ->first();

        if ($application === null) {
            return ApiResponse::success(data: null, message: 'No verification application found');
        }

        $resource = new VerificationApplicationResource($application);

        // Only worth computing the Didit URL while it's still actionable —
        // no point once decided, and it needs applicant_type + detail rows
        // loaded to know which workflow.
        if ($application->status === VerificationStatusEnum::IN_PROGRESS && $application->didit_session_id === null) {
            $resource->diditUrl = $this->didit->buildVerificationUrl($application->applicant_type, $application->uuid);
        }

        return ApiResponse::success(data: $resource, message: 'Verification status retrieved successfully');
    }
}
