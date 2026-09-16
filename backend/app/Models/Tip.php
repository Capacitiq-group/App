<?php

namespace App\Models;

use App\Enums\Tip\TipSourceTypeEnum;
use App\Enums\Tip\TipStatusEnum;
use App\Traits\HasUuidObservable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tip extends Model
{
    use HasUuidObservable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tipper_id',
        'recipient_id',
        'source_type',
        'source_id',
        'amount_cents',
        'platform_fee_cents',
        'recipient_amount_cents',
        'paystack_reference',
        'status',
        'message',
    ];

    /**
     * The default attributes for the model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'source_type' => 'profile',
        'status' => 'pending',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_type' => TipSourceTypeEnum::class,
            'status' => TipStatusEnum::class,
            'amount_cents' => 'integer',
            'platform_fee_cents' => 'integer',
            'recipient_amount_cents' => 'integer',
        ];
    }

    /**
     * Get the user who sent the tip.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function tipper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tipper_id');
    }

    /**
     * Get the user who received the tip.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Get the post this tip was sent from, if source_type is POST.
     * Manual lookup rather than a true polymorphic relation, since source_id
     * can point at either posts or spaces depending on source_type.
     */
    public function sourcePost(): ?Post
    {
        if ($this->source_type !== TipSourceTypeEnum::POST || $this->source_id === null) {
            return null;
        }

        return Post::find($this->source_id);
    }

    /**
     * Get the Space this tip was sent from, if source_type is SPACE.
     */
    public function sourceSpace(): ?Space
    {
        if ($this->source_type !== TipSourceTypeEnum::SPACE || $this->source_id === null) {
            return null;
        }

        return Space::find($this->source_id);
    }
}
