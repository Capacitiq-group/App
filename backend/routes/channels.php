<?php

/**
 * Channels API routes
 */

use App\Models\User;
use App\Repositories\SpaceParticipantRepository;
use App\Repositories\SpaceRepository;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('space.{spaceUuid}', function (User $user, string $spaceUuid) {
    $spaceRepository = app()->make(SpaceRepository::class);
    $participantRepository = app()->make(SpaceParticipantRepository::class);

    $space = $spaceRepository->query()->where('uuid', $spaceUuid)->first();

    if ($space === null) {
        return false;
    }

    $participant = $participantRepository->activeParticipant($space, $user->id);

    if ($participant === null) {
        return false;
    }

    // This becomes the presence channel's member info on the client —
    // enough to render an avatar + role without a follow-up API call.
    return [
        'id' => $user->id,
        'uuid' => $user->uuid,
        'username' => $user->username,
        'avatar_url' => $user->avatarFile?->url,
        'role' => $participant->role->value,
    ];
});
