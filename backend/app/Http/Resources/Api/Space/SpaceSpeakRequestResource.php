<?php

namespace App\Http\Resources\Api\Space;

use App\Http\Resources\Api\User\UserResource;
use App\Http\Resources\BaseJsonResource;

class SpaceSpeakRequestResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => UserResource::make($this->whenLoaded('user')),
            'status' => $this->status->value,
            'requested_at' => $this->requested_at->toDateTimeString(),
            'resolved_at' => $this->resolved_at?->toDateTimeString(),
        ];
    }
}
