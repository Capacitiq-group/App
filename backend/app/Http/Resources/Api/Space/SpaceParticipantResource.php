<?php

namespace App\Http\Resources\Api\Space;

use App\Http\Resources\Api\User\UserResource;
use App\Http\Resources\BaseJsonResource;

class SpaceParticipantResource extends BaseJsonResource
{
    /**
     * Optional Agora connection payload — set explicitly by the controller
     * right after a join, never persisted.
     */
    public ?array $agora = null;

    public function toArray($request): array
    {
        return [
            'user' => UserResource::make($this->whenLoaded('user')),
            'role' => $this->role->value,
            'is_muted' => $this->is_muted,
            'joined_at' => $this->joined_at->toDateTimeString(),
            'agora' => $this->when($this->agora !== null, $this->agora),
        ];
    }
}
