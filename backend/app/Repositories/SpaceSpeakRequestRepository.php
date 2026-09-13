<?php

namespace App\Repositories;

use App\Enums\Space\SpaceSpeakRequestStatusEnum;
use App\Models\Space;
use App\Models\SpaceSpeakRequest;
use Illuminate\Database\Eloquent\Collection;

class SpaceSpeakRequestRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(app()->make(SpaceSpeakRequest::class));
    }

    /**
     * Get the pending speak requests for a Space, oldest first.
     *
     * @return Collection<int, SpaceSpeakRequest>
     */
    public function pendingFor(Space $space): Collection
    {
        return $this->query()
            ->where('space_id', $space->id)
            ->where('status', SpaceSpeakRequestStatusEnum::PENDING->value)
            ->orderBy('requested_at')
            ->get();
    }

    /**
     * Find a user's currently pending speak request in a Space, if any.
     */
    public function pendingForUser(Space $space, int $userId): ?SpaceSpeakRequest
    {
        return $this->query()
            ->where('space_id', $space->id)
            ->where('user_id', $userId)
            ->where('status', SpaceSpeakRequestStatusEnum::PENDING->value)
            ->first();
    }
}
