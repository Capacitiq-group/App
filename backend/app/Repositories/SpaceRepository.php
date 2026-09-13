<?php

namespace App\Repositories;

use App\Enums\Space\SpaceStatusEnum;
use App\Models\Space;
use Illuminate\Database\Eloquent\Collection;

class SpaceRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(app()->make(Space::class));
    }

    /**
     * Find a Space by its public uuid, or throw NotFoundException.
     */
    public function findByUuidOrFail(string $uuid): Space
    {
        /** @var Space $space */
        $space = $this->findWhereFirstOrFail(['uuid' => $uuid]);

        return $space;
    }

    /**
     * Whether the given host already has a Space that hasn't ended yet
     * (status = waiting or live) — backs the "one active/scheduled Space at
     * a time per account" rule.
     */
    public function hasActiveOrWaitingSpace(int $hostUserId): bool
    {
        return $this->query()
            ->where('host_user_id', $hostUserId)
            ->whereIn('status', [SpaceStatusEnum::WAITING->value, SpaceStatusEnum::LIVE->value])
            ->exists();
    }

    /**
     * Count Spaces created by the given host in the last rolling 7 days —
     * backs the "3 Spaces per rolling 7 days" hosting rate limit.
     */
    public function countHostedInLast7Days(int $hostUserId): int
    {
        return $this->query()
            ->where('host_user_id', $hostUserId)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
    }

    /**
     * Get live Spaces whose duration has expired — for the auto-end background job.
     *
     * @return Collection<int, Space>
     */
    public function liveAndExpired(): Collection
    {
        return $this->query()
            ->where('status', SpaceStatusEnum::LIVE->value)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get();
    }

    /**
     * Get discoverable Spaces (waiting or live), most recent first, optionally
     * filtered by topic — backs the discovery feed.
     */
    public function discoverable(?int $topicId = null, int $perPage = 20)
    {
        return $this->query()
            ->whereIn('status', [SpaceStatusEnum::WAITING->value, SpaceStatusEnum::LIVE->value])
            ->when($topicId !== null, fn ($q) => $q->where('topic_id', $topicId))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
