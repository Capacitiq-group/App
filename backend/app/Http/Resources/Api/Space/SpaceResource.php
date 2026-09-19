<?php

namespace App\Http\Resources\Api\Space;

use App\Http\Resources\Api\User\UserResource;
use App\Http\Resources\BaseJsonResource;

class SpaceResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'topic_id' => $this->topic_id,
            'discovery_scope' => $this->discovery_scope->value,
            'type' => $this->type->value,
            'ticket_price_cents' => $this->ticket_price_cents,
            'status' => $this->status->value,
            'host' => UserResource::make($this->whenLoaded('host')),
            'min_to_start' => $this->min_to_start,
            'min_to_continue' => $this->min_to_continue,
            'max_participants' => $this->max_participants,
            'max_speakers' => $this->max_speakers,
            'duration_minutes' => $this->duration_minutes,
            'active_participant_count' => $this->whenCounted('activeParticipants'),
            'active_speaker_count' => $this->whenCounted('activeSpeakers'),
            'waiting_started_at' => $this->waiting_started_at?->toDateTimeString(),
            'activated_at' => $this->activated_at?->toDateTimeString(),
            'ends_at' => $this->ends_at?->toDateTimeString(),
            'ended_at' => $this->ended_at?->toDateTimeString(),
            'ended_reason' => $this->ended_reason?->value,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
