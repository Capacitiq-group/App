<?php

namespace App\Models;

use App\Enums\Space\SpaceParticipantRoleEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpaceParticipant extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'space_id',
        'user_id',
        'role',
        'agora_uid',
        'invited_by',
        'is_muted',
    ];

    /**
     * The default attributes for the model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role'     => 'listener',
        'is_muted' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role'       => SpaceParticipantRoleEnum::class,
            'agora_uid'  => 'integer',
            'is_muted'   => 'boolean',
            'joined_at'  => 'datetime',
            'left_at'    => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    /**
     * Get the Space this participant row belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Get the participating user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who invited this participant, if any.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the host/co-host who removed this participant, if applicable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    /**
     * Whether this participant session is currently active (neither left nor removed).
     */
    public function isActive(): bool
    {
        return $this->left_at === null && $this->removed_at === null;
    }

    /**
     * Scope a query to only include currently active participant sessions.
     *
     * @param  Builder  $query  The query builder instance.
     * @return Builder The modified query builder instance.
     */
    #[Scope]
    public function active(Builder $query): Builder
    {
        return $query->whereNull('left_at')->whereNull('removed_at');
    }
}
