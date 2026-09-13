<?php

namespace App\Console\Commands;

use App\Repositories\StoryRepository;
use Illuminate\Console\Command;

class PruneExpiredStoriesCommand extends Command
{
    protected $signature = 'stories:prune-expired';

    protected $description = 'Soft-delete ephemeral stories past the configured grace window after expiry.';

    public function handle(StoryRepository $storyRepository): int
    {
        $days = (int) config('story.prune_after_days');
        $expired = $storyRepository->expiredLongerThan($days);

        if ($expired->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($expired as $story) {
            $story->delete();
        }

        $this->info("Pruned {$expired->count()} expired story/stories.");

        return self::SUCCESS;
    }
}
