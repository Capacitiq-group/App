<?php

namespace App\Repositories;

use App\Models\Space;
use App\Models\SpaceParticipant;
use Illuminate\Database\Eloquent\Collection;

class SpaceParticipantRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(app()->make(SpaceParticipant::class));
    }

    /**
     * Get the currently active roster for a Space (neither left nor removed).
     *
     * @return Collection<int, SpaceParticipant>
     */
    public function activeFor(Space $space): Collection
    {
        return $this->query()
            ->where('space_id', $space->id)
            ->whereNull('left_at')
            ->whereNull('removed_at')
            ->get();
    }

    /**
     * Count the currently active participants in a Space.
     */
    public function activeCountFor(Space $space): int
    {
        return $this->query()
            ->where('space_id', $space->id)
            ->whereNull('left_at')
            ->whereNull('removed_at')
            ->count();
    }

    /**
     * Find the given user's currently active participant row in a Space, if any.
     */
    public function activeParticipant(Space $space, int $userId): ?SpaceParticipant
    {
        return $this->query()
            ->where('space_id', $space->id)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->whereNull('removed_at')
            ->first();
    }

    /**
     * Allocate the next free numeric Agora UID within a Space's channel.
     * Agora UIDs only need to be unique per-channel, not globally.
     */
    public function nextAgoraUid(Space $space): int
    {
        $max = $this->query()
            ->where('space_id', $space->id)
            ->max('agora_uid');

        return ((int) $max) + 1;
    }
}
