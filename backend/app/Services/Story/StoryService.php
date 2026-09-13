<?php

namespace App\Services\Story;

use App\Enums\Story\StoryTypeEnum;
use App\Exceptions\http\BusinessException;
use App\Exceptions\http\ForbiddenException;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use App\Repositories\StoryRepository;
use App\Repositories\StoryViewRepository;
use Illuminate\Support\Facades\DB;

class StoryService
{
    public function __construct(
        private readonly StoryRepository $storyRepository,
        private readonly StoryViewRepository $storyViewRepository,
    ) {}

    /**
     * Create a video, image (carousel-capable), or text story.
     *
     * @param  array{
     *     type: string,
     *     audience?: string,
     *     text_content?: string|null,
     *     background_color?: string|null,
     *     duration_seconds?: int|null,
     *     medias?: array<int, array{file_id: int, type: int}>,
     * }  $payload
     *
     * @throws BusinessException If the payload doesn't match the story type
     *                            (e.g. no media for an image story).
     */
    public function create(User $user, array $payload): Story
    {
        $type = StoryTypeEnum::from($payload['type']);

        if ($type->hasOwnMedia() && empty($payload['medias'])) {
            throw new BusinessException('At least one media item is required for this story type.');
        }

        if ($type === StoryTypeEnum::TEXT && empty($payload['text_content'])) {
            throw new BusinessException('Text content is required for a text story.');
        }

        return DB::transaction(function () use ($user, $payload, $type): Story {
            $story = $this->storyRepository->create([
                'user_id' => $user->id,
                'type' => $type->value,
                'audience' => $payload['audience'] ?? 'public',
                'text_content' => $payload['text_content'] ?? null,
                'background_color' => $payload['background_color'] ?? null,
                'duration_seconds' => $payload['duration_seconds'] ?? config('story.default_duration_seconds'),
                'expires_at' => now()->addHours((int) config('story.ttl_hours')),
            ]);

            if ($type->hasOwnMedia()) {
                foreach (array_values($payload['medias']) as $index => $media) {
                    $story->media()->create([
                        'type' => $media['type'],
                        'upload_file_id' => $media['file_id'],
                        'order' => $index,
                    ]);
                }
            }

            return $story->refresh();
        });
    }

    /**
     * Share an existing post to a story.
     */
    public function sharePost(User $user, Post $post, ?string $caption = null, ?string $audience = null): Story
    {
        return $this->storyRepository->create([
            'user_id' => $user->id,
            'type' => StoryTypeEnum::SHARED_POST->value,
            'audience' => $audience ?? 'public',
            'text_content' => $caption,
            'shared_post_id' => $post->id,
            'duration_seconds' => config('story.default_duration_seconds'),
            'expires_at' => now()->addHours((int) config('story.ttl_hours')),
        ]);
    }

    /**
     * Record a view. Re-viewing updates the timestamp rather than creating a
     * duplicate row or double-counting view_count.
     */
    public function view(Story $story, User $viewer): void
    {
        // Owners viewing their own story isn't a "view" for view_count/viewer-list purposes.
        if ($story->user_id === $viewer->id) {
            return;
        }

        $alreadyViewed = $this->storyViewRepository->hasViewed($story, $viewer->id);

        DB::table('story_views')->updateOrInsert(
            ['story_id' => $story->id, 'viewer_id' => $viewer->id],
            ['viewed_at' => now()],
        );

        if (! $alreadyViewed) {
            $story->increment('view_count');
        }
    }

    /**
     * Delete a story early (before its natural expiry/prune). Owner only.
     *
     * @throws ForbiddenException
     */
    public function delete(Story $story, User $actingUser): void
    {
        if ($story->user_id !== $actingUser->id) {
            throw new ForbiddenException('You can only delete your own story.');
        }

        $story->delete();
    }
}
