<?php

namespace App\Events\Space;

use App\Models\Space;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after SpaceService::acceptSpeakRequest() commits. Two consumers:
 *  1. A notification (see CreateSpeakerPromotedNotificationListener) —
 *     delivered via the poll-based /notifications endpoint, for anyone not
 *     currently connected to the room.
 *  2. This broadcast, on the Space's presence channel — tells the promoted
 *     user's client (and everyone else currently in the room) about the role
 *     change instantly. The promoted user's client should react by calling
 *     POST /spaces/{uuid}/token to get a fresh Agora token with publisher
 *     rights — the token itself is deliberately NOT included in this
 *     payload, since it would then be broadcast to every listener in the
 *     room on the same shared channel.
 *
 * Requires a queue worker running (`php artisan queue:work`) — this
 * implements ShouldBroadcast (queued), not ShouldBroadcastNow.
 */
class SpaceSpeakerPromotedEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $actorId,
        public readonly Space $space,
        public readonly int $promotedUserId,
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
        return 'speaker.promoted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'space_uuid' => $this->space->uuid,
            'promoted_user_id' => $this->promotedUserId,
            'actor_id' => $this->actorId,
        ];
    }
}
