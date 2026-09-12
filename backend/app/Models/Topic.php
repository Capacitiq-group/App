<?php

namespace App\Models;

use App\Traits\HasUuidObservable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Minimal stub backing the platform-curated Topics system. Only exists so
 * Spaces (and later, other content types) have something to tag — the full
 * Topics/discovery feature is a separate design effort.
 */
class Topic extends Model
{
    use HasUuidObservable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the Spaces tagged with this topic.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance.
     */
    public function spaces(): HasMany
    {
        return $this->hasMany(Space::class);
    }
}
