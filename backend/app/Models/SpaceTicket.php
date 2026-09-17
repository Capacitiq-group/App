<?php

namespace App\Models;

use App\Enums\Tip\TipStatusEnum;
use App\Traits\HasUuidObservable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpaceTicket extends Model
{
    use HasUuidObservable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'space_id',
        'buyer_id',
        'amount_cents',
        'platform_fee_cents',
        'host_amount_cents',
        'paystack_reference',
        'status',
    ];

    /**
     * The default attributes for the model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
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
            'status' => TipStatusEnum::class,
            'amount_cents' => 'integer',
            'platform_fee_cents' => 'integer',
            'host_amount_cents' => 'integer',
        ];
    }

    /**
     * Get the Space this ticket is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Get the buyer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
