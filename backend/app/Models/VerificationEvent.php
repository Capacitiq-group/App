<?php

namespace App\Models;

use App\Enums\Verification\VerificationEventTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationEvent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'verification_application_id',
        'event_type',
        'actor_id',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => VerificationEventTypeEnum::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the parent application.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(VerificationApplication::class, 'verification_application_id');
    }

    /**
     * Get the admin who triggered this event, if it wasn't system/webhook-triggered.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
