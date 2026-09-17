<?php

namespace App\Http\Resources\Api\Space;

use App\Http\Resources\BaseJsonResource;

class SpaceTicketResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'amount_cents' => $this->amount_cents,
            'status' => $this->status->value,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
