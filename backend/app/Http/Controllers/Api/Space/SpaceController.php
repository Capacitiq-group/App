<?php

namespace App\Http\Controllers\Api\Space;

use App\Enums\Space\SpaceEndedReasonEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Space\BanParticipantRequest;
use App\Http\Requests\Space\CreateSpaceRequest;
use App\Http\Requests\Space\EndSpaceRequest;
use App\Http\Requests\Space\JoinSpaceRequest;
use App\Http\Requests\Space\LeaveSpaceRequest;
use App\Http\Requests\Space\ListSpacesRequest;
use App\Http\Requests\Space\ModerateParticipantRequest;
use App\Http\Requests\Space\RefreshAgoraTokenRequest;
use App\Http\Requests\Space\RequestToSpeakRequest;
use App\Http\Requests\Space\ResolveSpeakRequestRequest;
use App\Http\Requests\Space\ShowSpaceRequest;
use App\Http\Resources\Api\Space\SpaceParticipantResource;
use App\Http\Resources\Api\Space\SpaceResource;
use App\Http\Resources\Api\Space\SpaceSpeakRequestResource;
use App\Http\Response\ApiResponse;
use App\Repositories\SpaceRepository;
use App\Repositories\UserRepository;
use App\Services\Space\SpaceService;
use App\Traits\HasAuthUser;
use Illuminate\Http\JsonResponse;

class SpaceController extends Controller
{
    use HasAuthUser;

    public function __construct(
        private readonly SpaceService $spaceService,
        private readonly SpaceRepository $spaceRepository,
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * Create a new Space. Enters the "waiting" state until min_to_start is reached.
     */
    public function create(CreateSpaceRequest $request): JsonResponse
    {
        $space = $this->spaceService->create($this->guard()->user(), $request->validated());

        return ApiResponse::created(
            data: new SpaceResource($space->load('host')),
            message: 'Space created — waiting for participants'
        );
    }

    /**
     * Get discoverable Spaces (waiting or live), optionally filtered by topic.
     */
    public function index(ListSpacesRequest $request): JsonResponse
    {
        $spaces = $this->spaceRepository->discoverable(
            topicId: $request->validated('topic_id'),
            perPage: $request->validated('per_page') ?? 20,
        );

        return ApiResponse::success(
            data: SpaceResource::collection($spaces),
            message: 'Spaces retrieved successfully'
        );
    }

    /**
     * Get a single Space by uuid.
     */
    public function show(ShowSpaceRequest $request): JsonResponse
    {
        $space = $this->spaceRepository->findByUuidOrFail($request->validated('space_uuid'));

        return ApiResponse::success(
            data: new SpaceResource($space->loadCount(['activeParticipants', 'activeSpeakers'])->load('host')),
            message: 'Space retrieved successfully'
        );
    }

    /**
     * Join a Space as a listener (or reconnect + refresh the Agora token if
     * already an active participant).
     */
    public function join(JoinSpaceRequest $request): JsonResponse
    {
        $space = $this->spaceRepository->findByUuidOrFail($request->validated('space_uuid'));

        $invitedByUuid = $request->validated('invited_by_user_uuid');
        $invitedById = $invitedByUuid !== null
            ? $this->userRepository->findByUuid($invitedByUuid)?->id
            : null;

        $result = $this->spaceService->join($space, $this->guard()->user(), $invitedById);

        $participantResource = new SpaceParticipantResource($result['participant']->load('user'));
        $participantResource->agora = $result['agora'];

        return ApiResponse::success(
            data: [
                'space' => new SpaceResource($result['space']),
                'participant' => $participantResource,
            ],
            message: 'Joined Space successfully'
        );
    }

    /**
     * Get a fresh Agora RTC token for the caller's current role — call this
     * after receiving a real-time role-change event (e.g. speaker.promoted).
     */
    public function refreshToken(RefreshAgoraTokenRequest $request): JsonResponse
    {
        $space = $this->spaceRepository->findByUuidOrFail($request->validated('space_uuid'));

        $agora = $this->spaceService->refreshAgoraToken($space, $this->guard()->user());

        return ApiResponse::success(
            data: ['agora' => $agora],
            message: 'Token refreshed'
        );
    }

    /**
     * Leave a Space.
     */
    public function leave(LeaveSpaceRequest $request): \Illuminate\Http\Response
    {
        $space = $this->spaceRepository->findByUuidOrFail($request->validated('space_uuid'));

        $this->spaceService->leave($space, $this->guard()->user());

        return ApiResponse::noContent();
    }

    /**
     * End a Space. Host only.
     */
    public function end(EndSpaceRequest $request): JsonResponse
    {
        $space = $this->spaceRepository->findByUuidOrFail($request->validated('space_uuid'));

        $space = $this->spaceService->end($space, $this->guard()->user(), SpaceEndedReasonEnum::HOST_ENDED);

        return ApiResponse::success(
            data: new SpaceResource($space),
            message: 'Space ended'
        );
    }

    /**
     * Request to speak (listener -> pending).
     */
    public function requestToSpeak(RequestToSpeakRequest $request): JsonResponse
    {
        $space = $this->spaceRepository->findByUuidOrFail($request->validated('space_uuid'));

        $speakRequest = $this->spaceService->requestToSpeak($space, $this->guard()->user());

        return ApiResponse::created(
            data: new SpaceSpeakRequestResource($speakRequest),
            message: 'Speak request sent'
        );
    }

    /**
     * Accept a pending speak request (host/co-host only).
     */
    public function acceptSpeakRequest(ResolveSpeakRequestRequest $request): JsonResponse
    {
        $space = $this->spaceRepository->findByUuidOrFail($request->validated('space_uuid'));

        $speakRequest = $this->spaceService->acceptSpeakRequest(
            $space,
            $this->guard()->user(),
            (int) $request->validated('speak_request_id'),
        );

        return ApiResponse::success(
            data: new SpaceSpeakRequestResource($speakRequest),
            message: 'Speak request accepted'
        );
    }

    /**
     * Decline a pending speak request (host/co-host only).
     */
    public function declineSpeakRequest(ResolveSpeakRequestRequest $request): JsonResponse
    {
        $space = $this->spaceRepository->findByUuidOrFail($request->validated('space_uuid'));

        $speakRequest = $this->spaceService->declineSpeakRequest(
            $space,
            $this->guard()->user(),
            (int) $request->validated('speak_request_id'),
        );

        return ApiResponse::success(
            data: new SpaceSpeakRequestResource($speakRequest),
            message: 'Speak request declined'
        );
    }

    /**
     * Mute a participant (host/co-host only).
     */
    public function mute(ModerateParticipantRequest $request): JsonResponse
    {
        return $this->toggleMute($request, true);
    }

    /**
     * Unmute a participant (host/co-host only).
     */
    public function unmute(ModerateParticipantRequest $request): JsonResponse
    {
        return $this->toggleMute($request, false);
    }

    /**
     * Remove a participant for this session (host/co-host only). Does not
     * block rejoining — see ban().
     */
    public function remove(ModerateParticipantRequest $request): \Illuminate\Http\Response
    {
        $space = $this->spaceRepository->findByUuidOrFail($request->validated('space_uuid'));
        $target = $this->userRepository->findByUuidOrFail($request->validated('target_user_uuid'));

        $this->spaceService->removeParticipant($space, $this->guard()->user(), $target->id);

        return ApiResponse::noContent();
    }

    /**
     * Remove a participant and block them from rejoining this Space (host/co-host only).
     */
    public function ban(BanParticipantRequest $request): \Illuminate\Http\Response
    {
        $space = $this->spaceRepository->findByUuidOrFail($request->validated('space_uuid'));
        $target = $this->userRepository->findByUuidOrFail($request->validated('target_user_uuid'));

        $this->spaceService->banParticipant(
            $space,
            $this->guard()->user(),
            $target->id,
            $request->validated('reason'),
        );

        return ApiResponse::noContent();
    }

    /**
     * Shared implementation for mute()/unmute().
     */
    private function toggleMute(ModerateParticipantRequest $request, bool $muted): JsonResponse
    {
        $space = $this->spaceRepository->findByUuidOrFail($request->validated('space_uuid'));
        $target = $this->userRepository->findByUuidOrFail($request->validated('target_user_uuid'));

        $participant = $this->spaceService->setMuted($space, $this->guard()->user(), $target->id, $muted);

        return ApiResponse::success(
            data: new SpaceParticipantResource($participant->load('user')),
            message: $muted ? 'Participant muted' : 'Participant unmuted'
        );
    }
}
