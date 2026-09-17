<?php

namespace App\Services\Space;

use App\Enums\Post\AudienceTypeEnum;
use App\Enums\Post\PostPublishStatusEnum;
use App\Enums\Space\SpaceEndedReasonEnum;
use App\Enums\Space\SpaceParticipantRoleEnum;
use App\Enums\Space\SpaceSpeakRequestStatusEnum;
use App\Enums\Space\SpaceStatusEnum;
use App\Enums\Space\SpaceTypeEnum;
use App\Events\Space\SpaceEndedEvent;
use App\Events\Space\SpaceParticipantMutedEvent;
use App\Events\Space\SpaceParticipantRemovedEvent;
use App\Events\Space\SpaceSpeakerPromotedEvent;
use App\Events\Space\SpaceSpeakRequestCreatedEvent;
use App\Events\Space\SpaceSpeakRequestDeclinedEvent;
use App\Exceptions\http\BusinessException;
use App\Exceptions\http\ForbiddenException;
use App\Exceptions\http\NotFoundException;
use App\Models\Space;
use App\Models\SpaceParticipant;
use App\Models\SpaceSpeakRequest;
use App\Models\User;
use App\Repositories\SpaceBanRepository;
use App\Repositories\SpaceParticipantRepository;
use App\Repositories\SpaceRepository;
use App\Repositories\SpaceSpeakRequestRepository;
use App\Repositories\SpaceTicketRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SpaceService
{
    public function __construct(
        private readonly SpaceRepository $spaceRepository,
        private readonly SpaceParticipantRepository $participantRepository,
        private readonly SpaceSpeakRequestRepository $speakRequestRepository,
        private readonly SpaceBanRepository $banRepository,
        private readonly SpaceTicketRepository $ticketRepository,
        private readonly AgoraTokenService $agoraTokenService,
        private readonly SpaceTicketService $ticketService,
    ) {}

    /**
     * Create a new Space. The host is added as its first active participant
     * immediately — "5 people join" is read as 5 total active participants
     * including the host, not 4 more besides them. Flagging this reading
     * explicitly since the original rule was stated in plain language.
     *
     * @param  array{title: string, description?: string|null, topic_id?: int|null, discovery_scope?: string, type?: string, ticket_price_cents?: int|null}  $payload
     *
     * @throws ForbiddenException|BusinessException If the host is not
     *          eligible, is rate-limited, already has an active/waiting
     *          Space, or tries to sell tickets without being eligible to.
     */
    public function create(User $host, array $payload): Space
    {
        $this->assertCanHost($host);

        $type = SpaceTypeEnum::from($payload['type'] ?? SpaceTypeEnum::PUBLIC->value);
        $ticketPriceCents = $payload['ticket_price_cents'] ?? null;

        if ($ticketPriceCents !== null) {
            $this->assertCanSellTickets($host, $type);
        }

        return DB::transaction(function () use ($host, $payload, $type, $ticketPriceCents): Space {
            $defaults = config('agora.defaults');

            $space = $this->spaceRepository->create([
                'host_user_id' => $host->id,
                'title' => $payload['title'],
                'description' => $payload['description'] ?? null,
                'topic_id' => $payload['topic_id'] ?? null,
                'discovery_scope' => $payload['discovery_scope'] ?? 'public',
                'type' => $type->value,
                'ticket_price_cents' => $ticketPriceCents,
                'status' => SpaceStatusEnum::WAITING->value,
                'min_to_start' => $defaults['min_to_start'],
                'min_to_continue' => $defaults['min_to_continue'],
                'max_participants' => $defaults['max_participants'],
                'max_speakers' => $defaults['max_speakers'],
                'duration_minutes' => $defaults['duration_minutes'],
                'agora_channel_name' => 'space_'.Str::uuid()->toString(),
                'waiting_started_at' => now(),
            ]);

            $this->participantRepository->create([
                'space_id' => $space->id,
                'user_id' => $host->id,
                'role' => SpaceParticipantRoleEnum::HOST->value,
                'agora_uid' => 1,
                'joined_at' => now(),
            ]);

            return $space->refresh();
        });
    }

    /**
     * Invite a user to a Private Space. Host/co-host only.
     *
     * @throws ForbiddenException|BusinessException
     */
    public function inviteToPrivateSpace(Space $space, User $actingUser, User $invitee): void
    {
        $this->assertCanModerate($space, $actingUser);

        if (! $space->isPrivate()) {
            throw new BusinessException('Only Private Spaces use invitations — this Space is open to everyone it\'s discoverable to.');
        }

        $space->invitations()->firstOrCreate(
            ['invited_user_id' => $invitee->id],
            ['invited_by_user_id' => $actingUser->id],
        );
    }

    /**
     * Buy a ticket for a Creator Space. Returns the pending ticket and the
     * Paystack checkout URL.
     *
     * @return array{ticket: \App\Models\SpaceTicket, authorization_url: string}
     *
     * @throws BusinessException
     */
    public function purchaseTicket(Space $space, User $buyer, string $callbackUrl): array
    {
        if (! $space->isTicketed()) {
            throw new BusinessException('This Space does not require a ticket.');
        }

        if ($space->host_user_id === $buyer->id) {
            throw new BusinessException('You already have access as the host.');
        }

        return $this->ticketService->purchase($space, $buyer, $callbackUrl);
    }

    /**
     * Join a Space as a listener (or return the existing session + a fresh
     * token if already active). Activates the Space if this join reaches
     * min_to_start.
     *
     * @return array{space: Space, participant: SpaceParticipant, agora: array}
     *
     * @throws BusinessException|ForbiddenException
     */
    public function join(Space $space, User $user, ?int $invitedBy = null): array
    {
        if ($space->isEnded()) {
            throw new BusinessException('This Space has ended.');
        }

        if ($this->banRepository->isBanned($space, $user->id)) {
            throw new ForbiddenException('You have been removed from this Space and cannot rejoin.');
        }

        $isHost = $space->host_user_id === $user->id;

        if (! $isHost && $space->isPrivate()) {
            $isInvited = $space->invitations()->where('invited_user_id', $user->id)->exists();

            if (! $isInvited) {
                throw new ForbiddenException('This is a Private Space — you need an invitation to join.');
            }
        }

        if (! $isHost && $space->isTicketed()) {
            $hasTicket = $this->ticketRepository->hasCompletedTicket($space, $user->id);

            if (! $hasTicket) {
                throw new BusinessException('This Space requires a ticket — purchase one to join.');
            }
        }

        $existing = $this->participantRepository->activeParticipant($space, $user->id);

        if ($existing !== null) {
            return [
                'space' => $space,
                'participant' => $existing,
                'agora' => $this->agoraTokenService->generateRtcToken(
                    $space->agora_channel_name,
                    $existing->agora_uid,
                    $existing->role,
                ),
            ];
        }

        $activeCount = $this->participantRepository->activeCountFor($space);

        if ($activeCount >= $space->max_participants) {
            throw new BusinessException('This Space is full.');
        }

        $participant = DB::transaction(function () use ($space, $user, $invitedBy): SpaceParticipant {
            // Lock the space row so two concurrent joins can't both read the
            // same pre-insert count and race for "who activated the Space".
            $lockedSpace = Space::whereKey($space->id)->lockForUpdate()->first();
            $activeCountUnderLock = $this->participantRepository->activeCountFor($lockedSpace);

            $participant = $this->participantRepository->create([
                'space_id' => $space->id,
                'user_id' => $user->id,
                'role' => SpaceParticipantRoleEnum::LISTENER->value,
                'agora_uid' => $this->participantRepository->nextAgoraUid($space),
                'invited_by' => $invitedBy,
                'joined_at' => now(),
            ]);

            if ($lockedSpace->isWaiting() && ($activeCountUnderLock + 1) >= $lockedSpace->min_to_start) {
                $lockedSpace->update([
                    'status' => SpaceStatusEnum::LIVE->value,
                    'activated_at' => now(),
                    'ends_at' => now()->addMinutes($lockedSpace->duration_minutes),
                ]);
            }

            return $participant;
        });

        return [
            'space' => $space->refresh(),
            'participant' => $participant,
            'agora' => $this->agoraTokenService->generateRtcToken(
                $space->agora_channel_name,
                $participant->agora_uid,
                $participant->role,
            ),
        ];
    }

    /**
     * Leave a Space. Handles the min_to_continue and host-left rules.
     */
    public function leave(Space $space, User $user): void
    {
        $participant = $this->participantRepository->activeParticipant($space, $user->id);

        if ($participant === null) {
            return;
        }

        DB::transaction(function () use ($space, $participant) {
            $participant->update(['left_at' => now()]);
            $this->reconcileAfterDeparture($space, $participant);
        });
    }

    /**
     * Request to speak. The listener must be an active participant with no
     * existing pending request.
     */
    public function requestToSpeak(Space $space, User $user): SpaceSpeakRequest
    {
        $participant = $this->participantRepository->activeParticipant($space, $user->id);

        if ($participant === null) {
            throw new ForbiddenException('You must be in the Space to request to speak.');
        }

        if ($participant->role !== SpaceParticipantRoleEnum::LISTENER) {
            throw new BusinessException('You are already able to speak in this Space.');
        }

        if ($this->speakRequestRepository->pendingForUser($space, $user->id) !== null) {
            throw new BusinessException('You already have a pending request to speak.');
        }

        $speakRequest = $this->speakRequestRepository->create([
            'space_id' => $space->id,
            'user_id' => $user->id,
            'status' => SpaceSpeakRequestStatusEnum::PENDING->value,
            'requested_at' => now(),
        ]);

        event(new SpaceSpeakRequestCreatedEvent($space, $speakRequest->id, $user->id));

        return $speakRequest;
    }

    /**
     * Get the pending speak requests for a Space, oldest first. Host/co-host only.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, SpaceSpeakRequest>
     */
    public function listPendingSpeakRequests(Space $space, User $actingUser): \Illuminate\Database\Eloquent\Collection
    {
        $this->assertCanModerate($space, $actingUser);

        return $this->speakRequestRepository->pendingFor($space);
    }

    /**
     * Accept a pending speak request, promoting the requester to speaker.
     *
     * @throws BusinessException If the request isn't pending or the Space is
     *                            already at its speaker cap.
     * @throws ForbiddenException If the acting user can't moderate this Space.
     */
    public function acceptSpeakRequest(Space $space, User $actingUser, int $speakRequestId): SpaceSpeakRequest
    {
        $this->assertCanModerate($space, $actingUser);

        $speakRequest = $this->findPendingSpeakRequestOrFail($space, $speakRequestId);

        $activeSpeakerCount = $space->activeSpeakers()->count();

        if ($activeSpeakerCount >= $space->max_speakers) {
            throw new BusinessException('This Space has reached its maximum number of speakers.');
        }

        DB::transaction(function () use ($space, $actingUser, $speakRequest) {
            $speakRequest->update([
                'status' => SpaceSpeakRequestStatusEnum::ACCEPTED->value,
                'resolved_at' => now(),
                'resolved_by' => $actingUser->id,
            ]);

            $this->participantRepository->activeParticipant($space, $speakRequest->user_id)
                ?->update(['role' => SpaceParticipantRoleEnum::SPEAKER->value]);

            event(new SpaceSpeakerPromotedEvent($actingUser->id, $space, $speakRequest->user_id));
        });

        return $speakRequest->refresh();
    }

    /**
     * Decline a pending speak request.
     */
    public function declineSpeakRequest(Space $space, User $actingUser, int $speakRequestId): SpaceSpeakRequest
    {
        $this->assertCanModerate($space, $actingUser);

        $speakRequest = $this->findPendingSpeakRequestOrFail($space, $speakRequestId);

        $speakRequest->update([
            'status' => SpaceSpeakRequestStatusEnum::DECLINED->value,
            'resolved_at' => now(),
            'resolved_by' => $actingUser->id,
        ]);

        event(new SpaceSpeakRequestDeclinedEvent($space, $speakRequest->id, $speakRequest->user_id));

        return $speakRequest->refresh();
    }

    /**
     * Mute or unmute an active participant.
     */
    public function setMuted(Space $space, User $actingUser, int $targetUserId, bool $muted): SpaceParticipant
    {
        $this->assertCanModerate($space, $actingUser);

        $target = $this->participantRepository->activeParticipant($space, $targetUserId);

        if ($target === null) {
            throw new NotFoundException('Participant not found in this Space.');
        }

        $target->update(['is_muted' => $muted]);

        event(new SpaceParticipantMutedEvent($space, $targetUserId, $muted, $actingUser->id));

        return $target->refresh();
    }

    /**
     * Remove a participant from the Space for this session. Does not block
     * them from rejoining — use banParticipant() for that.
     *
     * @param  bool  $banned  Internal — set by banParticipant() so the
     *                         broadcast event reflects a ban rather than a
     *                         plain removal. Not meant to be passed directly.
     */
    public function removeParticipant(Space $space, User $actingUser, int $targetUserId, bool $banned = false): void
    {
        $this->assertCanModerate($space, $actingUser);

        $target = $this->participantRepository->activeParticipant($space, $targetUserId);

        if ($target === null) {
            throw new NotFoundException('Participant not found in this Space.');
        }

        if ($target->user_id === $space->host_user_id) {
            throw new BusinessException('The host cannot be removed. End the Space instead.');
        }

        DB::transaction(function () use ($space, $actingUser, $target) {
            $target->update(['removed_at' => now(), 'removed_by' => $actingUser->id]);
            $this->reconcileAfterDeparture($space, $target);
        });

        event(new SpaceParticipantRemovedEvent($space, $targetUserId, $banned, $actingUser->id));
    }

    /**
     * Remove a participant and block them from rejoining this Space.
     */
    public function banParticipant(Space $space, User $actingUser, int $targetUserId, ?string $reason = null): void
    {
        $this->removeParticipant($space, $actingUser, $targetUserId, banned: true);

        DB::table('space_bans')->insertOrIgnore([
            'space_id' => $space->id,
            'user_id' => $targetUserId,
            'banned_by' => $actingUser->id,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * End a Space. Only the host may end it directly (co-hosts can moderate
     * but not end, per "Host: 1 person" in the V1 rules).
     */
    public function end(Space $space, User $actingUser, SpaceEndedReasonEnum $reason = SpaceEndedReasonEnum::HOST_ENDED): Space
    {
        if ($space->host_user_id !== $actingUser->id && $reason === SpaceEndedReasonEnum::HOST_ENDED) {
            throw new ForbiddenException('Only the host can end this Space.');
        }

        DB::transaction(function () use ($space, $reason) {
            $space->update([
                'status' => SpaceStatusEnum::ENDED->value,
                'ended_at' => now(),
                'ended_reason' => $reason->value,
            ]);

            $this->participantRepository->activeFor($space)->each(
                fn (SpaceParticipant $participant) => $participant->update(['left_at' => now()])
            );
        });

        event(new SpaceEndedEvent($space, $reason->value));

        return $space->refresh();
    }

    /**
     * Issue a fresh Agora RTC token for the caller's current role in this
     * Space. Clients call this after receiving a `speaker.promoted` (or any
     * role-changing) broadcast, rather than the role-change payload itself
     * ever carrying a token — a token is credentials, and the presence
     * channel is shared by everyone in the room.
     *
     * @return array{token: string, app_id: string, channel_name: string, uid: int, expires_at: int}
     *
     * @throws ForbiddenException If the caller isn't currently an active participant.
     */
    public function refreshAgoraToken(Space $space, User $user): array
    {
        $participant = $this->participantRepository->activeParticipant($space, $user->id);

        if ($participant === null) {
            throw new ForbiddenException('You are not currently in this Space.');
        }

        return $this->agoraTokenService->generateRtcToken(
            $space->agora_channel_name,
            $participant->agora_uid,
            $participant->role,
        );
    }

    /**
     * Re-check min_to_continue / empty-waiting-room after someone leaves or
     * is removed, ending the Space automatically if the rule is hit.
     *
     * ASSUMPTION (flagged, not confirmed): if the host leaves and no co-host
     * remains to moderate, the Space ends immediately with HOST_LEFT — a
     * Space with no one able to mute/remove/end isn't viable. Worth
     * confirming this is the intended behaviour.
     */
    private function reconcileAfterDeparture(Space $space, SpaceParticipant $departed): void
    {
        $space->refresh();

        if ($space->isEnded()) {
            return;
        }

        $wasHost = $departed->user_id === $space->host_user_id;
        $activeCount = $this->participantRepository->activeCountFor($space);

        if ($wasHost) {
            $remainingCoHost = $space->activeParticipants()
                ->where('role', SpaceParticipantRoleEnum::CO_HOST->value)
                ->exists();

            if (! $remainingCoHost) {
                $this->end($space, $departed->user, SpaceEndedReasonEnum::HOST_LEFT);

                return;
            }
        }

        if ($space->isWaiting() && $activeCount === 0) {
            $this->end($space, $departed->user, SpaceEndedReasonEnum::AUTO_CLOSED_EMPTY);

            return;
        }

        if ($space->isLive() && $activeCount < $space->min_to_continue) {
            $this->end($space, $departed->user, SpaceEndedReasonEnum::BELOW_MINIMUM);
        }
    }

    /**
     * Check host eligibility and throw with the specific reason if not met.
     *
     * NOTE: two of the rules from the V1 spec aren't enforced yet, deliberately:
     *  - 2FA ("preferably") — no 2FA column exists on users yet; stated as a
     *    soft preference, not a hard gate, so left as a future addition.
     *  - "not currently restricted from Spaces" — there's no dedicated
     *    restriction flag distinct from a global ban; this currently just
     *    reuses User::isBanned(). Add a real flag if a lighter, Spaces-only
     *    restriction is needed later.
     */
    public function assertCanHost(User $host): void
    {
        $minAccountAgeDays = (int) config('agora.host_min_account_age_days');
        $minPublicPosts = (int) config('agora.host_min_public_posts');
        $minAgeYears = (int) config('agora.host_min_age_years');
        $maxPerWeek = (int) config('agora.host_max_per_rolling_week');

        if ($host->created_at->diffInDays(now()) < $minAccountAgeDays) {
            throw new ForbiddenException("Your account must be at least {$minAccountAgeDays} days old to host a Space.");
        }

        if (! $host->hasVerifiedEmail()) {
            throw new ForbiddenException('You must verify your email to host a Space.');
        }

        if (empty($host->avatar_file_id) || empty($host->bio)) {
            throw new ForbiddenException('Complete your profile (photo and bio) to host a Space.');
        }

        $publicPostCount = $host->posts()
            ->where('audience', AudienceTypeEnum::PUBLIC->value)
            ->where('status', PostPublishStatusEnum::PUBLISHED->value)
            ->count();

        if ($publicPostCount < $minPublicPosts) {
            throw new ForbiddenException("You need at least {$minPublicPosts} public posts to host a Space.");
        }

        if ($host->isBanned()) {
            throw new ForbiddenException('Your account is not in good standing.');
        }

        if ((bool) config('agora.host_requires_verification') && ! $host->isVerified()) {
            throw new ForbiddenException('Only verified accounts can host a Space right now.');
        }

        if ($minAgeYears > 0 && ($host->age() === null || $host->age() < $minAgeYears)) {
            throw new ForbiddenException("You must be {$minAgeYears}+ to host a Space.");
        }

        if ($this->spaceRepository->hasActiveOrWaitingSpace($host->id)) {
            throw new BusinessException('You already have an active or waiting Space.');
        }

        if ($this->spaceRepository->countHostedInLast7Days($host->id) >= $maxPerWeek) {
            throw new BusinessException("You can only host {$maxPerWeek} Spaces per rolling 7 days.");
        }
    }

    /**
     * Ticketed Spaces are always Creator-type and always require a verified
     * host — unlike general hosting eligibility, this isn't behind the
     * host_requires_verification "soon" flag. Selling tickets is a today
     * requirement per the platform spec ("a verified creator can create a
     * ticketed Space"), independent of whether hosting in general has been
     * locked down to verified users yet.
     *
     * @throws ForbiddenException|BusinessException
     */
    private function assertCanSellTickets(User $host, SpaceTypeEnum $type): void
    {
        if (! $type->canBeTicketed()) {
            throw new BusinessException('Only Creator Spaces can sell tickets.');
        }

        if (! $host->isVerified()) {
            throw new ForbiddenException('You must be a verified creator to sell tickets for a Space.');
        }
    }

    /**
     * Whether the given user can moderate the Space (host or co-host, and
     * currently an active participant).
     */
    private function assertCanModerate(Space $space, User $user): void
    {
        $participant = $this->participantRepository->activeParticipant($space, $user->id);

        if ($participant === null || ! $participant->role->canModerate()) {
            throw new ForbiddenException('You do not have permission to moderate this Space.');
        }
    }

    /**
     * Find a pending speak request in this Space by ID, or throw.
     */
    private function findPendingSpeakRequestOrFail(Space $space, int $speakRequestId): SpaceSpeakRequest
    {
        $speakRequest = $space->speakRequests()
            ->whereKey($speakRequestId)
            ->where('status', SpaceSpeakRequestStatusEnum::PENDING->value)
            ->first();

        if ($speakRequest === null) {
            throw new NotFoundException('Pending speak request not found.');
        }

        return $speakRequest;
    }
}
