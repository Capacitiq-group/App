<?php

namespace App\Http\Resources\Api\Highlight;

use App\Http\Resources\Api\Story\StoryResource;
use App\Http\Resources\BaseJsonResource;

class HighlightResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'cover_url' => $this->whenLoaded('coverFile', fn () => $this->coverFile?->url),
            'position' => $this->position,
            'story_count' => $this->whenCounted('stories'),
            'stories' => StoryResource::collection($this->whenLoaded('stories')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
