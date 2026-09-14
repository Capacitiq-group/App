<?php

namespace App\Models;

use App\Enums\Verification\VerificationBusinessRoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationBusinessDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'verification_application_id',
        'legal_entity_name',
        'is_cipc_registered',
        'registration_number',
        'registration_country',
        'representative_full_name',
        'representative_role',
        'representative_role_other',
        'sole_trader_trading_name',
        'sole_trader_tax_vat_number',
        'sole_trader_address_or_bank',
        'matched_cipc_officer',
        'matched_officer_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_cipc_registered' => 'boolean',
            'representative_role' => VerificationBusinessRoleEnum::class,
            'matched_cipc_officer' => 'boolean',
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
     * Whether this application is eligible to grant business-account control
     * per Synkra's policy (see the migration's comment): a CIPC-registered
     * business only qualifies once the applicant's personal check matched a
     * real listed officer; a sole trader always qualifies on their own KYC,
     * since there's no separate entity to cross-match against.
     */
    public function qualifiesForAccountControl(): bool
    {
        if (! $this->is_cipc_registered) {
            return true;
        }

        return $this->matched_cipc_officer === true;
    }
}
