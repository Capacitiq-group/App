<?php

namespace App\Repositories;

use App\Models\Story;
use App\Models\StoryView;
use Illuminate\Database\Eloquent\Collection;

class StoryViewRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(app()->make(StoryView::class));
    }

    /**
     * Whether the given viewer has already viewed this story.
     */
    public function hasViewed(Story $story, int $viewerId): bool
    {
        return $this->query()
            ->where('story_id', $story->id)
            ->where('viewer_id', $viewerId)
            ->exists();
    }

    /**
     * Get the viewers of a story, most recent view first — "who viewed my story."
     *
     * @return Collection<int, StoryView>
     */
    public function viewersFor(Story $story): Collection
    {
        return $this->query()
            ->where('story_id', $story->id)
            ->orderByDesc('viewed_at')
            ->get();
    }
}
