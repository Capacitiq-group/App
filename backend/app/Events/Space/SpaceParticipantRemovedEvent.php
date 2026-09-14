<?php

namespace App\Events\Space;

use App\Models\Space;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SpaceParticipantRemovedEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Space $space,
        public readonly int $targetUserId,
        public readonly bool $banned,
        public readonly int $actorId,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel("space.{$this->space->uuid}")];
    }

    public function broadcastAs(): string
    {
        return 'participant.removed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'space_uuid' => $this->space->uuid,
            'target_user_id' => $this->targetUserId,
            'banned' => $this->banned,
            'actor_id' => $this->actorId,
        ];
    }
}
