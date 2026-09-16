<?php

namespace App\Http\Requests\Verification;

use App\Enums\Verification\VerificationApplicantTypeEnum;
use App\Enums\Verification\VerificationBillingCycleEnum;
use App\Enums\Verification\VerificationBusinessRoleEnum;
use App\Enums\Verification\VerificationIndividualTypeEnum;
use App\Enums\Verification\VerificationPoliticalEntityTypeEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rules\Enum;

class CreateVerificationApplicationRequest extends BaseRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules([
            'applicant_type' => [
                self::REQUIRED,
                new Enum(VerificationApplicantTypeEnum::class),
            ],
            'billing_cycle' => [
                self::REQUIRED,
                new Enum(VerificationBillingCycleEnum::class),
            ],
            'callback_url' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':500',
            ],

            // Individual (Form A)
            'full_legal_name' => [
                self::REQUIRED_IF.':applicant_type,individual',
                self::SOMETIMES,
                self::STRING,
                self::MAX.':150',
            ],
            'verifying_as' => [
                self::REQUIRED_IF.':applicant_type,individual',
                self::SOMETIMES,
                new Enum(VerificationIndividualTypeEnum::class),
            ],
            'verifying_as_other' => [
                self::REQUIRED_IF.':verifying_as,other',
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':100',
            ],
            'supporting_link' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':500',
            ],
            'id_issuing_country' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':2',
            ],

            // Business (Form B)
            'legal_entity_name' => [
                self::REQUIRED_IF.':applicant_type,business',
                self::SOMETIMES,
                self::STRING,
                self::MAX.':200',
            ],
            'is_cipc_registered' => [
                self::SOMETIMES,
                self::BOOLEAN,
            ],
            'registration_number' => [
                self::REQUIRED_IF.':is_cipc_registered,true',
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':50',
            ],
            'registration_country' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':2',
            ],
            'representative_full_name' => [
                self::REQUIRED_IF.':applicant_type,business,political_entity',
                self::SOMETIMES,
                self::STRING,
                self::MAX.':150',
            ],
            'representative_role' => [
                self::REQUIRED_IF.':applicant_type,business',
                self::SOMETIMES,
                new Enum(VerificationBusinessRoleEnum::class),
            ],
            'representative_role_other' => [
                self::REQUIRED_IF.':representative_role,other',
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':100',
            ],
            'sole_trader_trading_name' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':200',
            ],
            'sole_trader_tax_vat_number' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':50',
            ],
            'sole_trader_address_or_bank' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':255',
            ],

            // Political / Government Entity (Form C)
            'official_entity_name' => [
                self::REQUIRED_IF.':applicant_type,political_entity',
                self::SOMETIMES,
                self::STRING,
                self::MAX.':200',
            ],
            'entity_type' => [
                self::REQUIRED_IF.':applicant_type,political_entity',
                self::SOMETIMES,
                new Enum(VerificationPoliticalEntityTypeEnum::class),
            ],
            'entity_type_other' => [
                self::REQUIRED_IF.':entity_type,other',
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':100',
            ],
            'registration_or_gazette_reference' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':200',
            ],
            'representative_role_title' => [
                self::REQUIRED_IF.':applicant_type,political_entity',
                self::SOMETIMES,
                self::STRING,
                self::MAX.':100',
            ],
        ]);
    }

    /**
     * Split the validated payload into applicant_type + the detail fields
     * for that type only — matches what VerificationService::submit() expects.
     *
     * @return array<string, mixed>
     */
    public function detailFields(): array
    {
        $validated = $this->validated();

        $fieldsByType = [
            VerificationApplicantTypeEnum::INDIVIDUAL->value => [
                'full_legal_name', 'verifying_as', 'verifying_as_other', 'supporting_link', 'id_issuing_country',
            ],
            VerificationApplicantTypeEnum::BUSINESS->value => [
                'legal_entity_name', 'is_cipc_registered', 'registration_number', 'registration_country',
                'representative_full_name', 'representative_role', 'representative_role_other',
                'sole_trader_trading_name', 'sole_trader_tax_vat_number', 'sole_trader_address_or_bank',
            ],
            VerificationApplicantTypeEnum::POLITICAL_ENTITY->value => [
                'official_entity_name', 'entity_type', 'entity_type_other',
                'registration_or_gazette_reference', 'representative_full_name', 'representative_role_title',
            ],
        ];

        $fields = $fieldsByType[$validated['applicant_type']] ?? [];

        return array_filter(
            array_intersect_key($validated, array_flip($fields)),
            fn ($value) => $value !== null,
        );
    }
}
