<?php

namespace App\Enums\Verification;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum VerificationApplicantTypeEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case INDIVIDUAL       = 'individual';
    case BUSINESS         = 'business';
    case POLITICAL_ENTITY = 'political_entity';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL       => 'Individual',
            self::BUSINESS         => 'Business',
            self::POLITICAL_ENTITY => 'Political Party / Government Entity',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::INDIVIDUAL       => 'Cá nhân',
            self::BUSINESS         => 'Doanh nghiệp',
            self::POLITICAL_ENTITY => 'Đảng phái / Cơ quan chính phủ',
        };
    }
}
