<?php

namespace App\Repositories;

use App\Models\Space;
use App\Models\SpaceBan;

class SpaceBanRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(app()->make(SpaceBan::class));
    }

    /**
     * Whether the given user is banned from the given Space.
     */
    public function isBanned(Space $space, int $userId): bool
    {
        return $this->query()
            ->where('space_id', $space->id)
            ->where('user_id', $userId)
            ->exists();
    }
}
