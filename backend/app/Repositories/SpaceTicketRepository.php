<?php

namespace App\Repositories;

use App\Enums\Tip\TipStatusEnum;
use App\Models\Space;
use App\Models\SpaceTicket;

class SpaceTicketRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(app()->make(SpaceTicket::class));
    }

    /**
     * Whether the given user holds a completed ticket for this Space.
     */
    public function hasCompletedTicket(Space $space, int $userId): bool
    {
        return $this->query()
            ->where('space_id', $space->id)
            ->where('buyer_id', $userId)
            ->where('status', TipStatusEnum::COMPLETED->value)
            ->exists();
    }

    /**
     * Find a ticket by its Paystack reference.
     */
    public function findByReference(string $reference): ?SpaceTicket
    {
        return $this->query()->where('paystack_reference', $reference)->first();
    }
}
