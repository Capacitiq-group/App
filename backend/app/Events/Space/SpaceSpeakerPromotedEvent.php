<?php

namespace App\Events\Space;

use App\Models\Space;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after SpaceService::acceptSpeakRequest() commits. Two consumers are
 * expected:
 *  1. A notification (built here) — works today, delivered via the existing
 *     poll-based /notifications endpoint.
 *  2. A real-time push telling the promoted user's client to fetch a fresh
 *     Agora token with publisher rights — NOT built here. This event class
 *     is a plain Dispatchable, not ShouldBroadcast, because the repo's
 *     BROADCAST_CONNECTION defaults to 'log' (no Reverb/Pusher configured
 *     yet) — adding ShouldBroadcast now would silently do nothing rather
 *     than actually deliver anything. Once a real broadcast driver is
 *     configured, add `implements ShouldBroadcast` + a broadcastOn()
 *     returning a `new PrivateChannel("space.{$this->space->uuid}")` here.
 */
class SpaceSpeakerPromotedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $actorId,
        public readonly Space $space,
        public readonly int $promotedUserId,
    ) {}
}
