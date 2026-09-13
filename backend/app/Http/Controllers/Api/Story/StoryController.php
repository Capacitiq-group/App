<?php

namespace App\Http\Controllers\Api\Story;

use App\Exceptions\http\ForbiddenException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Story\CreateStoryRequest;
use App\Http\Requests\Story\DeleteStoryRequest;
use App\Http\Requests\Story\ListStoriesRequest;
use App\Http\Requests\Story\ListStoryViewersRequest;
use App\Http\Requests\Story\SharePostToStoryRequest;
use App\Http\Requests\Story\ShowStoryRequest;
use App\Http\Requests\Story\ViewStoryRequest;
use App\Http\Resources\Api\Story\StoryResource;
use App\Http\Resources\Api\Story\StoryViewResource;
use App\Http\Response\ApiResponse;
use App\Repositories\PostRepository;
use App\Repositories\StoryRepository;
use App\Repositories\StoryViewRepository;
use App\Repositories\UserRepository;
use App\Services\Story\StoryService;
use App\Traits\HasAuthUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class StoryController extends Controller
{
    use HasAuthUser;

    public function __construct(
        private readonly StoryService $storyService,
        private readonly StoryRepository $storyRepository,
        private readonly StoryViewRepository $storyViewRepository,
        private readonly UserRepository $userRepository,
        private readonly PostRepository $postRepository,
    ) {}

    /**
     * Create a story (video/image carousel/text).
     */
    public function create(CreateStoryRequest $request): JsonResponse
    {
        $story = $this->storyService->create($this->guard()->user(), $request->validated());

        return ApiResponse::created(
            data: new StoryResource($story->load(['user', 'media'])),
            message: 'Story created'
        );
    }

    /**
     * Share an existing post to a story.
     */
    public function sharePost(SharePostToStoryRequest $request): JsonResponse
    {
        $post = $this->postRepository->findByUuidOrFail($request->validated('post_uuid'));

        $story = $this->storyService->sharePost(
            $this->guard()->user(),
            $post,
            $request->validated('text_content'),
            $request->validated('audience'),
        );

        return ApiResponse::created(
            data: new StoryResource($story->load(['user', 'sharedPost'])),
            message: 'Shared to your story'
        );
    }

    /**
     * Get the stories tray: a specific profile's active stories if user_uuid
     * is given, otherwise the auth user's own stories plus everyone they follow.
     */
    public function index(ListStoriesRequest $request): JsonResponse
    {
        $authUser = $this->guard()->user();

        if ($request->validated('user_uuid')) {
            $profileUser = $this->userRepository->findByUuidOrFail($request->validated('user_uuid'));
            $stories = $this->storyRepository->activeForUser($profileUser->id);
        } else {
            $userIds = $authUser->followings()->pluck('users.id')->push($authUser->id)->all();
            $stories = $this->storyRepository->activeForUsers($userIds);
        }

        return ApiResponse::success(
            data: StoryResource::collection($stories->load(['user', 'media', 'sharedPost'])),
            message: 'Stories retrieved successfully'
        );
    }

    /**
     * Get a single story.
     */
    public function show(ShowStoryRequest $request): JsonResponse
    {
        $story = $this->storyRepository->findByUuidOrFail($request->validated('story_uuid'));

        return ApiResponse::success(
            data: new StoryResource($story->load(['user', 'media', 'sharedPost'])),
            message: 'Story retrieved successfully'
        );
    }

    /**
     * Mark a story as viewed by the authenticated user.
     */
    public function view(ViewStoryRequest $request): Response
    {
        $story = $this->storyRepository->findByUuidOrFail($request->validated('story_uuid'));

        $this->storyService->view($story, $this->guard()->user());

        return ApiResponse::noContent();
    }

    /**
     * Get who viewed a story. Owner only.
     */
    public function viewers(ListStoryViewersRequest $request): JsonResponse
    {
        $story = $this->storyRepository->findByUuidOrFail($request->validated('story_uuid'));

        if ($story->user_id !== $this->guard()->id()) {
            throw new ForbiddenException('You can only view the viewer list for your own story.');
        }

        $views = $this->storyViewRepository->viewersFor($story);

        return ApiResponse::success(
            data: StoryViewResource::collection($views->load('viewer')),
            message: 'Viewers retrieved successfully'
        );
    }

    /**
     * Delete a story early. Owner only.
     */
    public function destroy(DeleteStoryRequest $request): Response
    {
        $story = $this->storyRepository->findByUuidOrFail($request->validated('story_uuid'));

        $this->storyService->delete($story, $this->guard()->user());

        return ApiResponse::noContent();
    }
}
