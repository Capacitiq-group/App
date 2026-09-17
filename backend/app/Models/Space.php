<?php

namespace App\Models;

use App\Enums\Space\SpaceDiscoveryScopeEnum;
use App\Enums\Space\SpaceEndedReasonEnum;
use App\Enums\Space\SpaceParticipantRoleEnum;
use App\Enums\Space\SpaceStatusEnum;
use App\Enums\Space\SpaceTypeEnum;
use App\Traits\HasUuidObservable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Space extends Model
{
    use HasUuidObservable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'host_user_id',
        'title',
        'description',
        'topic_id',
        'discovery_scope',
        'type',
        'ticket_price_cents',
        'status',
        'min_to_start',
        'min_to_continue',
        'max_participants',
        'max_speakers',
        'duration_minutes',
        'agora_channel_name',
    ];

    /**
     * The default attributes for the model. Mirrors the migration defaults so
     * application code can rely on these without re-reading config.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'discovery_scope'  => 'public',
        'type'             => 'public',
        'status'           => 'waiting',
        'min_to_start'     => 5,
        'min_to_continue'  => 2,
        'max_participants' => 50,
        'max_speakers'     => 8,
        'duration_minutes' => 30,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discovery_scope'    => SpaceDiscoveryScopeEnum::class,
            'type'               => SpaceTypeEnum::class,
            'ticket_price_cents' => 'integer',
            'status'             => SpaceStatusEnum::class,
            'ended_reason'       => SpaceEndedReasonEnum::class,
            'min_to_start'       => 'integer',
            'min_to_continue'    => 'integer',
            'max_participants'   => 'integer',
            'max_speakers'       => 'integer',
            'duration_minutes'   => 'integer',
            'waiting_started_at' => 'datetime',
            'activated_at'       => 'datetime',
            'ends_at'            => 'datetime',
            'ended_at'           => 'datetime',
        ];
    }

    /**
     * Get the user hosting the Space.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    /**
     * Get the topic this Space is tagged with, if any.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get every join/leave session for this Space (full attendance history).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(SpaceParticipant::class);
    }

    /**
     * Get the currently active roster — participants who have neither left nor
     * been removed.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance.
     */
    public function activeParticipants(): HasMany
    {
        return $this->participants()->whereNull('left_at')->whereNull('removed_at');
    }

    /**
     * Get the currently active speakers (host, co-host, and speaker roles).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance.
     */
    public function activeSpeakers(): HasMany
    {
        return $this->activeParticipants()->whereIn('role', [
            SpaceParticipantRoleEnum::HOST->value,
            SpaceParticipantRoleEnum::CO_HOST->value,
            SpaceParticipantRoleEnum::SPEAKER->value,
        ]);
    }

    /**
     * Get every speak request made in this Space.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance.
     */
    public function speakRequests(): HasMany
    {
        return $this->hasMany(SpaceSpeakRequest::class);
    }

    /**
     * Get the pending speak requests, oldest first — the host's "hands up" queue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance.
     */
    public function pendingSpeakRequests(): HasMany
    {
        return $this->speakRequests()
            ->where('status', 'pending')
            ->orderBy('requested_at');
    }

    /**
     * Get the users banned from this Space.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance.
     */
    public function bans(): HasMany
    {
        return $this->hasMany(SpaceBan::class);
    }

    /**
     * Get this Space's invitation allowlist (Private Spaces only).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(SpaceInvitation::class);
    }

    /**
     * Get the tickets sold for this Space.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(SpaceTicket::class);
    }

    /**
     * Whether this Space requires an invitation to join.
     */
    public function isPrivate(): bool
    {
        return $this->type === SpaceTypeEnum::PRIVATE;
    }

    /**
     * Whether this Space requires a purchased ticket to join.
     */
    public function isTicketed(): bool
    {
        return $this->ticket_price_cents !== null;
    }

    /**
     * Whether the Space is still waiting to reach its activation threshold.
     */
    public function isWaiting(): bool
    {
        return $this->status === SpaceStatusEnum::WAITING;
    }

    /**
     * Whether the Space is currently live (activated, timer running).
     */
    public function isLive(): bool
    {
        return $this->status === SpaceStatusEnum::LIVE;
    }

    /**
     * Whether the Space has ended.
     */
    public function isEnded(): bool
    {
        return $this->status === SpaceStatusEnum::ENDED;
    }

    /**
     * Scope a query to only include Spaces that are currently live or waiting
     * (i.e. not yet ended) — used for the "one active/scheduled Space per
     * account" check and for discovery feeds.
     *
     * @param  Builder  $query  The query builder instance.
     * @return Builder The modified query builder instance.
     */
    #[Scope]
    public function notEnded(Builder $query): Builder
    {
        return $query->whereIn('status', [
            SpaceStatusEnum::WAITING->value,
            SpaceStatusEnum::LIVE->value,
        ]);
    }

    /**
     * Scope a query to only include Spaces created by the given host within
     * the last 7 rolling days — backs the "3 Spaces per rolling 7 days" limit.
     *
     * @param  Builder  $query  The query builder instance.
     * @param  int  $hostUserId  The host's user ID.
     * @return Builder The modified query builder instance.
     */
    #[Scope]
    public function hostedInLast7Days(Builder $query, int $hostUserId): Builder
    {
        return $query
            ->where('host_user_id', $hostUserId)
            ->where('created_at', '>=', now()->subDays(7));
    }
}
