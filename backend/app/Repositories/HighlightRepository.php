<?php

namespace App\Repositories;

use App\Models\Highlight;
use Illuminate\Database\Eloquent\Collection;

class HighlightRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(app()->make(Highlight::class));
    }

    /**
     * Find a Highlight by its public uuid, or throw NotFoundException.
     */
    public function findByUuidOrFail(string $uuid): Highlight
    {
        /** @var Highlight $highlight */
        $highlight = $this->findWhereFirstOrFail(['uuid' => $uuid]);

        return $highlight;
    }

    /**
     * Get a user's Highlights, in profile display order.
     *
     * @return Collection<int, Highlight>
     */
    public function forUser(int $userId): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->orderBy('position')
            ->get();
    }

    /**
     * Get the next position value for a new Highlight on this profile
     * (appends to the end of the row).
     */
    public function nextPosition(int $userId): int
    {
        $max = $this->query()->where('user_id', $userId)->max('position');

        return ((int) $max) + 1;
    }
}
