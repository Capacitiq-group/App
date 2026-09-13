<?php

namespace App\Models;

use App\Enums\Post\AudienceTypeEnum;
use App\Enums\Story\StoryTypeEnum;
use App\Traits\HasUuidObservable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Story extends Model
{
    use HasUuidObservable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'audience',
        'text_content',
        'background_color',
        'shared_post_id',
        'duration_seconds',
        'expires_at',
    ];

    /**
     * The default attributes for the model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'image',
        'audience' => 'public',
        'duration_seconds' => 5,
        'view_count' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StoryTypeEnum::class,
            'audience' => AudienceTypeEnum::class,
            'duration_seconds' => 'integer',
            'view_count' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user who posted the story.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post this story shares, if type is SHARED_POST.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function sharedPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'shared_post_id');
    }

    /**
     * Get the ordered media items for IMAGE/VIDEO (incl. carousel) stories.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance.
     */
    public function media(): HasMany
    {
        return $this->hasMany(StoryMedia::class)->orderBy('order');
    }

    /**
     * Get the view records for this story.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance.
     */
    public function views(): HasMany
    {
        return $this->hasMany(StoryView::class);
    }

    /**
     * Get the Highlights this story has been added to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The relationship instance.
     */
    public function highlights(): BelongsToMany
    {
        return $this->belongsToMany(Highlight::class, 'highlight_stories')
            ->withPivot(['position', 'added_at'])
            ->withTimestamps(false);
    }

    /**
     * Whether the story is permanent (added to at least one Highlight) —
     * mirrors the expires_at-is-null convention rather than re-querying
     * highlight_stories.
     */
    public function isPermanent(): bool
    {
        return $this->expires_at === null;
    }

    /**
     * Whether the story has expired (ephemeral and past its TTL).
     */
    public function isExpired(): bool
    {
        return ! $this->isPermanent() && $this->expires_at->isPast();
    }

    /**
     * Scope a query to only include currently visible (non-expired) stories
     * — i.e. permanent ones, or ephemeral ones still within their 24h window.
     *
     * @param  Builder  $query  The query builder instance.
     * @return Builder The modified query builder instance.
     */
    #[Scope]
    public function active(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
