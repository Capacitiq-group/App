<?php

namespace App\Http\Resources\Api\Tip;

use App\Http\Resources\Api\User\UserResource;
use App\Http\Resources\BaseJsonResource;

class TipResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'tipper' => UserResource::make($this->whenLoaded('tipper')),
            'recipient' => UserResource::make($this->whenLoaded('recipient')),
            'source_type' => $this->source_type->value,
            'amount_cents' => $this->amount_cents,
            'platform_fee_cents' => $this->platform_fee_cents,
            'recipient_amount_cents' => $this->recipient_amount_cents,
            'status' => $this->status->value,
            'message' => $this->message,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
