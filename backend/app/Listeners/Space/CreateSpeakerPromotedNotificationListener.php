<?php

namespace App\Listeners\Space;

use App\Events\Space\SpaceSpeakerPromotedEvent;
use App\Services\Notification\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreateSpeakerPromotedNotificationListener implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(private readonly NotificationService $notificationService)
    {
        $this->onQueue('notifications');
        $this->afterCommit = true;
    }

    public function handle(SpaceSpeakerPromotedEvent $event): void
    {
        $this->notificationService->notifySpeakerPromotion(
            actorId: $event->actorId,
            space: $event->space,
            promotedUserId: $event->promotedUserId,
        );
    }

    public function failed(SpaceSpeakerPromotedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to create speaker-promoted notification', [
            'actor_id' => $event->actorId,
            'space_id' => $event->space->id,
            'promoted_user_id' => $event->promotedUserId,
            'error' => $exception->getMessage(),
        ]);
    }
}
