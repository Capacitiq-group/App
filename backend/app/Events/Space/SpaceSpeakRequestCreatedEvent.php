<?php

namespace App\Events\Space;

use App\Models\Space;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SpaceSpeakRequestCreatedEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Space $space,
        public readonly int $speakRequestId,
        public readonly int $requestingUserId,
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
        return 'speak-request.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'space_uuid' => $this->space->uuid,
            'speak_request_id' => $this->speakRequestId,
            'requesting_user_id' => $this->requestingUserId,
        ];
    }
}
