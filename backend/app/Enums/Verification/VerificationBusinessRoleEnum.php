<?php

namespace App\Enums\Verification;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum VerificationBusinessRoleEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case DIRECTOR_OWNER      = 'director_owner';
    case OFFICER_EXECUTIVE    = 'officer_executive';
    case AUTHORIZED_EMPLOYEE  = 'authorized_employee';
    case SOLE_TRADER_OWNER    = 'sole_trader_owner';
    case OTHER                 = 'other';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::DIRECTOR_OWNER     => 'Director / Owner',
            self::OFFICER_EXECUTIVE   => 'Officer / Executive',
            self::AUTHORIZED_EMPLOYEE => 'Authorized Employee',
            self::SOLE_TRADER_OWNER   => 'Sole Trader (owner)',
            self::OTHER                => 'Other',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::DIRECTOR_OWNER     => 'Giám đốc / Chủ sở hữu',
            self::OFFICER_EXECUTIVE   => 'Cán bộ / Điều hành',
            self::AUTHORIZED_EMPLOYEE => 'Nhân viên được ủy quyền',
            self::SOLE_TRADER_OWNER   => 'Chủ hộ kinh doanh cá thể',
            self::OTHER                => 'Khác',
        };
    }
}
