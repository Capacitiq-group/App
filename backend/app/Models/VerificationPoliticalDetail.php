<?php

namespace App\Models;

use App\Enums\Verification\VerificationPoliticalEntityTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationPoliticalDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'verification_application_id',
        'official_entity_name',
        'entity_type',
        'entity_type_other',
        'registration_or_gazette_reference',
        'representative_full_name',
        'representative_role_title',
        'manually_confirmed_authority',
        'manual_confirmation_channel',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entity_type' => VerificationPoliticalEntityTypeEnum::class,
            'manually_confirmed_authority' => 'boolean',
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
}
