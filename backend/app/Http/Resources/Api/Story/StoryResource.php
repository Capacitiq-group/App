<?php

namespace App\Http\Resources\Api\Story;

use App\Http\Resources\Api\Post\PostResource;
use App\Http\Resources\Api\User\UserResource;
use App\Http\Resources\BaseJsonResource;

class StoryResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type->value,
            'audience' => $this->audience->value,
            'user' => UserResource::make($this->whenLoaded('user')),
            'text_content' => $this->text_content,
            'background_color' => $this->background_color,
            'media' => StoryMediaResource::collection($this->whenLoaded('media')),
            'shared_post' => PostResource::make($this->whenLoaded('sharedPost')),
            'duration_seconds' => $this->duration_seconds,
            'view_count' => $this->view_count,
            'is_permanent' => $this->isPermanent(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
