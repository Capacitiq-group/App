<?php

namespace App\Http\Controllers\Api\Highlight;

use App\Http\Controllers\Controller;
use App\Http\Requests\Highlight\AddStoryToHighlightRequest;
use App\Http\Requests\Highlight\CreateHighlightRequest;
use App\Http\Requests\Highlight\DeleteHighlightRequest;
use App\Http\Requests\Highlight\ListHighlightsRequest;
use App\Http\Requests\Highlight\RemoveStoryFromHighlightRequest;
use App\Http\Requests\Highlight\ShowHighlightRequest;
use App\Http\Requests\Highlight\UpdateHighlightRequest;
use App\Http\Resources\Api\Highlight\HighlightResource;
use App\Http\Response\ApiResponse;
use App\Repositories\HighlightRepository;
use App\Repositories\StoryRepository;
use App\Repositories\UserRepository;
use App\Services\Highlight\HighlightService;
use App\Traits\HasAuthUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class HighlightController extends Controller
{
    use HasAuthUser;

    public function __construct(
        private readonly HighlightService $highlightService,
        private readonly HighlightRepository $highlightRepository,
        private readonly StoryRepository $storyRepository,
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * Create a Highlight, optionally seeded with existing stories.
     */
    public function create(CreateHighlightRequest $request): JsonResponse
    {
        $initialStories = collect($request->validated('story_uuids', []))
            ->map(fn (string $uuid) => $this->storyRepository->findByUuidOrFail($uuid))
            ->all();

        $highlight = $this->highlightService->create(
            $this->guard()->user(),
            $request->validated(),
            $initialStories,
        );

        return ApiResponse::created(
            data: new HighlightResource($highlight->load(['coverFile', 'stories.media', 'stories.user'])),
            message: 'Highlight created'
        );
    }

    /**
     * Get a profile's Highlights.
     */
    public function index(ListHighlightsRequest $request): JsonResponse
    {
        $user = $this->userRepository->findByUuidOrFail($request->validated('user_uuid'));

        $highlights = $this->highlightRepository->forUser($user->id);

        return ApiResponse::success(
            data: HighlightResource::collection($highlights->loadCount('stories')->load('coverFile')),
            message: 'Highlights retrieved successfully'
        );
    }

    /**
     * Get a single Highlight with its stories.
     */
    public function show(ShowHighlightRequest $request): JsonResponse
    {
        $highlight = $this->highlightRepository->findByUuidOrFail($request->validated('highlight_uuid'));

        return ApiResponse::success(
            data: new HighlightResource($highlight->load(['coverFile', 'stories.media', 'stories.user'])),
            message: 'Highlight retrieved successfully'
        );
    }

    /**
     * Update a Highlight's title/cover. Owner only.
     */
    public function update(UpdateHighlightRequest $request): JsonResponse
    {
        $highlight = $this->highlightRepository->findByUuidOrFail($request->validated('highlight_uuid'));

        $highlight = $this->highlightService->update($highlight, $this->guard()->user(), $request->validated());

        return ApiResponse::success(
            data: new HighlightResource($highlight->load('coverFile')),
            message: 'Highlight updated'
        );
    }

    /**
     * Delete a Highlight. Owner only.
     */
    public function destroy(DeleteHighlightRequest $request): Response
    {
        $highlight = $this->highlightRepository->findByUuidOrFail($request->validated('highlight_uuid'));

        $this->highlightService->delete($highlight, $this->guard()->user());

        return ApiResponse::noContent();
    }

    /**
     * Add a story to a Highlight (making it permanent). Owner only.
     */
    public function addStory(AddStoryToHighlightRequest $request): JsonResponse
    {
        $highlight = $this->highlightRepository->findByUuidOrFail($request->validated('highlight_uuid'));
        $story = $this->storyRepository->findByUuidOrFail($request->validated('story_uuid'));

        $this->highlightService->addStory($highlight, $this->guard()->user(), $story);

        return ApiResponse::success(
            data: new HighlightResource($highlight->refresh()->load(['coverFile', 'stories.media', 'stories.user'])),
            message: 'Story added to Highlight'
        );
    }

    /**
     * Remove a story from a Highlight (does not delete the story). Owner only.
     */
    public function removeStory(RemoveStoryFromHighlightRequest $request): Response
    {
        $highlight = $this->highlightRepository->findByUuidOrFail($request->validated('highlight_uuid'));
        $story = $this->storyRepository->findByUuidOrFail($request->validated('story_uuid'));

        $this->highlightService->removeStory($highlight, $this->guard()->user(), $story);

        return ApiResponse::noContent();
    }
}
