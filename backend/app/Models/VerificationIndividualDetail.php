<?php

namespace App\Models;

use App\Enums\Verification\VerificationIndividualTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationIndividualDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'verification_application_id',
        'full_legal_name',
        'verifying_as',
        'verifying_as_other',
        'supporting_link',
        'id_issuing_country',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verifying_as' => VerificationIndividualTypeEnum::class,
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
