<?php

namespace App\Enums\Verification;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum VerificationPoliticalEntityTypeEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case POLITICAL_PARTY        = 'political_party';
    case GOVERNMENT_DEPARTMENT   = 'government_department';
    case STATE_OWNED_ENTITY      = 'state_owned_entity';
    case OTHER                    = 'other';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::POLITICAL_PARTY      => 'Political party',
            self::GOVERNMENT_DEPARTMENT => 'Government department/ministry',
            self::STATE_OWNED_ENTITY    => 'State-owned entity',
            self::OTHER                  => 'Other',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::POLITICAL_PARTY      => 'Đảng phái chính trị',
            self::GOVERNMENT_DEPARTMENT => 'Cơ quan/bộ chính phủ',
            self::STATE_OWNED_ENTITY    => 'Doanh nghiệp nhà nước',
            self::OTHER                  => 'Khác',
        };
    }
}
