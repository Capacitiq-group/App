<?php

namespace App\Repositories;

use App\Models\Tip;

class TipRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(app()->make(Tip::class));
    }

    /**
     * Find a tip by its Paystack reference.
     */
    public function findByReference(string $reference): ?Tip
    {
        return $this->query()->where('paystack_reference', $reference)->first();
    }

    /**
     * Find a tip by its public uuid, or throw NotFoundException.
     */
    public function findByUuidOrFail(string $uuid): Tip
    {
        /** @var Tip $tip */
        $tip = $this->findWhereFirstOrFail(['uuid' => $uuid]);

        return $tip;
    }
}
