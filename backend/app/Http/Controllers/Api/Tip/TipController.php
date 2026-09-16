<?php

namespace App\Http\Controllers\Api\Tip;

use App\Enums\Tip\TipSourceTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tip\CreateTipRequest;
use App\Http\Resources\Api\Tip\TipResource;
use App\Http\Response\ApiResponse;
use App\Repositories\PostRepository;
use App\Repositories\SpaceRepository;
use App\Repositories\UserRepository;
use App\Services\Tip\TipService;
use App\Traits\HasAuthUser;
use Illuminate\Http\JsonResponse;

class TipController extends Controller
{
    use HasAuthUser;

    public function __construct(
        private readonly TipService $tipService,
        private readonly UserRepository $userRepository,
        private readonly PostRepository $postRepository,
        private readonly SpaceRepository $spaceRepository,
    ) {}

    public function create(CreateTipRequest $request): JsonResponse
    {
        $recipient = $this->userRepository->findByUuidOrFail($request->validated('recipient_user_uuid'));
        $sourceType = TipSourceTypeEnum::from($request->validated('source_type'));
        $sourceId = $this->resolveSourceId($sourceType, $request->validated('source_uuid'));

        $callbackUrl = (string) $request->validated('callback_url', config('app.frontend_url').'/tips/callback');

        $result = $this->tipService->createTip(
            tipper: $this->guard()->user(),
            recipient: $recipient,
            amountCents: (int) $request->validated('amount_cents'),
            sourceType: $sourceType,
            sourceId: $sourceId,
            message: $request->validated('message'),
            callbackUrl: $callbackUrl,
        );

        return ApiResponse::created(
            data: [
                'tip' => new TipResource($result['tip']->load(['tipper', 'recipient'])),
                'paystack_authorization_url' => $result['authorization_url'],
            ],
            message: 'Tip started — complete payment to send it'
        );
    }

    /**
     * Resolve source_uuid to the right table's internal id based on
     * source_type. Profile tips have no source to resolve.
     */
    private function resolveSourceId(TipSourceTypeEnum $sourceType, ?string $sourceUuid): ?int
    {
        if ($sourceUuid === null) {
            return null;
        }

        return match ($sourceType) {
            TipSourceTypeEnum::POST => $this->postRepository->findByUuidOrFail($sourceUuid)->id,
            TipSourceTypeEnum::SPACE => $this->spaceRepository->findByUuidOrFail($sourceUuid)->id,
            TipSourceTypeEnum::PROFILE => null,
        };
    }
}
