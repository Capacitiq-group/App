<?php

namespace App\Services\Highlight;

use App\Exceptions\http\BusinessException;
use App\Exceptions\http\ForbiddenException;
use App\Models\Highlight;
use App\Models\Story;
use App\Models\User;
use App\Repositories\HighlightRepository;
use Illuminate\Support\Facades\DB;

class HighlightService
{
    public function __construct(
        private readonly HighlightRepository $highlightRepository,
    ) {}

    /**
     * Create a Highlight, optionally seeding it with existing stories.
     *
     * @param  array{title: string, cover_file_id?: int|null}  $payload
     * @param  array<int, Story>  $initialStories  Must all belong to $user — validated by the controller/request layer.
     */
    public function create(User $user, array $payload, array $initialStories = []): Highlight
    {
        return DB::transaction(function () use ($user, $payload, $initialStories): Highlight {
            $highlight = $this->highlightRepository->create([
                'user_id' => $user->id,
                'title' => $payload['title'],
                'cover_upload_file_id' => $payload['cover_file_id'] ?? null,
                'position' => $this->highlightRepository->nextPosition($user->id),
            ]);

            foreach (array_values($initialStories) as $index => $story) {
                $this->attachStory($highlight, $story, $index);
            }

            return $highlight->refresh();
        });
    }

    /**
     * Update a Highlight's title and/or cover. Owner only.
     *
     * @param  array{title?: string, cover_file_id?: int|null}  $payload
     *
     * @throws ForbiddenException
     */
    public function update(Highlight $highlight, User $actingUser, array $payload): Highlight
    {
        $this->assertOwner($highlight, $actingUser);

        $highlight->update([
            'title' => $payload['title'] ?? $highlight->title,
            'cover_upload_file_id' => array_key_exists('cover_file_id', $payload)
                ? $payload['cover_file_id']
                : $highlight->cover_upload_file_id,
        ]);

        return $highlight->refresh();
    }

    /**
     * Delete a Highlight. The stories it contained stay permanent — see
     * addStory() for why removal from a Highlight never reverts a story back
     * to ephemeral.
     *
     * @throws ForbiddenException
     */
    public function delete(Highlight $highlight, User $actingUser): void
    {
        $this->assertOwner($highlight, $actingUser);

        $highlight->delete();
    }

    /**
     * Add a story to a Highlight, making it permanent.
     *
     * ASSUMPTION (flagged): once a story has been added to any Highlight, it
     * stays permanent (expires_at null) even if later removed from every
     * Highlight it was in — matching how most apps treat a personal
     * "archive" as separate from curated Highlights, rather than trying to
     * recompute an expiry for a story whose original 24h window has long
     * since passed. Worth confirming this is the behaviour you want.
     *
     * @throws ForbiddenException|BusinessException
     */
    public function addStory(Highlight $highlight, User $actingUser, Story $story): void
    {
        $this->assertOwner($highlight, $actingUser);

        if ($story->user_id !== $actingUser->id) {
            throw new ForbiddenException('You can only add your own stories to a Highlight.');
        }

        if ($highlight->stories()->where('story_id', $story->id)->exists()) {
            throw new BusinessException('This story is already in this Highlight.');
        }

        DB::transaction(function () use ($highlight, $story) {
            $nextPosition = ((int) $highlight->stories()->max('highlight_stories.position')) + 1;
            $this->attachStory($highlight, $story, $nextPosition);
        });
    }

    /**
     * Remove a story from a Highlight. Does not delete the story itself and
     * does not revert its permanence — see addStory().
     *
     * @throws ForbiddenException
     */
    public function removeStory(Highlight $highlight, User $actingUser, Story $story): void
    {
        $this->assertOwner($highlight, $actingUser);

        $highlight->stories()->detach($story->id);
    }

    /**
     * Attach a story to a highlight at the given position and make it permanent.
     */
    private function attachStory(Highlight $highlight, Story $story, int $position): void
    {
        $highlight->stories()->attach($story->id, [
            'position' => $position,
            'added_at' => now(),
        ]);

        if ($story->expires_at !== null) {
            $story->update(['expires_at' => null]);
        }
    }

    /**
     * @throws ForbiddenException
     */
    private function assertOwner(Highlight $highlight, User $user): void
    {
        if ($highlight->user_id !== $user->id) {
            throw new ForbiddenException('You do not have permission to manage this Highlight.');
        }
    }
}
