<?php

namespace App\Repositories;

use App\Models\Story;
use Illuminate\Database\Eloquent\Collection;

class StoryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(app()->make(Story::class));
    }

    /**
     * Find a Story by its public uuid, or throw NotFoundException.
     */
    public function findByUuidOrFail(string $uuid): Story
    {
        /** @var Story $story */
        $story = $this->findWhereFirstOrFail(['uuid' => $uuid]);

        return $story;
    }

    /**
     * Get a user's currently active stories (own tray / a profile's stories),
     * oldest first (the natural viewing order for one person's stories).
     *
     * @return Collection<int, Story>
     */
    public function activeForUser(int $userId): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->active()
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get active stories from a set of user IDs (a follow feed's stories
     * tray), most-recently-posted user first, oldest-story-first within a user.
     *
     * @param  array<int, int>  $userIds
     * @return Collection<int, Story>
     */
    public function activeForUsers(array $userIds): Collection
    {
        return $this->query()
            ->whereIn('user_id', $userIds)
            ->active()
            ->orderBy('user_id')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get ephemeral (non-permanent) stories that expired more than the given
     * number of days ago — for the prune background job. Permanent
     * (highlighted) stories are never returned here.
     *
     * @return Collection<int, Story>
     */
    public function expiredLongerThan(int $days): Collection
    {
        return $this->query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->subDays($days))
            ->get();
    }
}
