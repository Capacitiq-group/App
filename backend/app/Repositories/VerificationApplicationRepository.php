<?php

namespace App\Repositories;

use App\Enums\Verification\VerificationHoldStatusEnum;
use App\Enums\Verification\VerificationStatusEnum;
use App\Models\VerificationApplication;

class VerificationApplicationRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(app()->make(VerificationApplication::class));
    }

    /**
     * Find an application by its public uuid, or null.
     */
    public function findByUuid(string $uuid): ?VerificationApplication
    {
        return $this->query()->where('uuid', $uuid)->first();
    }

    /**
     * Find an application by its public uuid, or throw NotFoundException.
     */
    public function findByUuidOrFail(string $uuid): VerificationApplication
    {
        /** @var VerificationApplication $application */
        $application = $this->findWhereFirstOrFail(['uuid' => $uuid]);

        return $application;
    }

    /**
     * Whether the user has an application that hasn't reached a terminal
     * state yet — backs the "one application at a time" rule.
     */
    public function hasOpenApplication(int $userId): bool
    {
        return $this->query()
            ->where('user_id', $userId)
            ->whereNotIn('status', [VerificationStatusEnum::VERIFIED->value, VerificationStatusEnum::FAILED->value])
            ->exists();
    }

    /**
     * Get applications that are still open (in_progress/under_review/
     * extra_review_needed) with a hold that has already expired — the
     * reconciliation job's target set.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, VerificationApplication>
     */
    public function withExpiredHoldsAwaitingDecision(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->query()
            ->whereNotIn('status', [VerificationStatusEnum::VERIFIED->value, VerificationStatusEnum::FAILED->value])
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->where('hold_status', '!=', VerificationHoldStatusEnum::EXPIRED->value)
            ->get();
    }
}
