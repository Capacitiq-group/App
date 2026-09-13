<?php

namespace App\Console\Commands;

use App\Enums\Space\SpaceEndedReasonEnum;
use App\Repositories\SpaceRepository;
use App\Services\Space\SpaceService;
use Illuminate\Console\Command;

class EndExpiredSpacesCommand extends Command
{
    protected $signature = 'spaces:end-expired';

    protected $description = 'End live Spaces whose duration limit (ends_at) has passed.';

    public function handle(SpaceRepository $spaceRepository, SpaceService $spaceService): int
    {
        $expired = $spaceRepository->liveAndExpired();

        if ($expired->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($expired as $space) {
            // end() normally requires the acting user to be the host for a
            // HOST_ENDED reason, but that check is skipped for any other
            // reason — see SpaceService::end(). The host user is still
            // passed through so participant/attribution records stay
            // consistent (e.g. who owns the closed-out session).
            $spaceService->end($space, $space->host, SpaceEndedReasonEnum::TIMER_EXPIRED);
        }

        $this->info("Ended {$expired->count()} expired Space(s).");

        return self::SUCCESS;
    }
}
