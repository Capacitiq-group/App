<?php

namespace App\Http\Resources\Api\Story;

use App\Http\Resources\Api\User\UserResource;
use App\Http\Resources\BaseJsonResource;

class StoryViewResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'viewer' => UserResource::make($this->whenLoaded('viewer')),
            'viewed_at' => $this->viewed_at->toDateTimeString(),
        ];
    }
}
