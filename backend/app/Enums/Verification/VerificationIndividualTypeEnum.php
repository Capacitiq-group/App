<?php

namespace App\Enums\Verification;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum VerificationIndividualTypeEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case ORDINARY_USER  = 'ordinary_user';
    case CREATOR        = 'creator';
    case ARTIST         = 'artist';
    case PUBLIC_FIGURE  = 'public_figure';
    case OTHER          = 'other';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::ORDINARY_USER => 'Just me (ordinary user)',
            self::CREATOR        => 'Creator',
            self::ARTIST          => 'Artist / Musician',
            self::PUBLIC_FIGURE  => 'Public figure',
            self::OTHER           => 'Other',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::ORDINARY_USER => 'Chỉ là tôi (người dùng thường)',
            self::CREATOR        => 'Nhà sáng tạo',
            self::ARTIST          => 'Nghệ sĩ / Nhạc sĩ',
            self::PUBLIC_FIGURE  => 'Nhân vật công chúng',
            self::OTHER           => 'Khác',
        };
    }

    /**
     * The badge label shown on the profile once this application is approved.
     */
    public function badgeLabel(): string
    {
        return match ($this) {
            self::ORDINARY_USER => 'Verified User',
            self::CREATOR        => 'Verified Creator',
            self::ARTIST          => 'Verified Artist',
            self::PUBLIC_FIGURE  => 'Verified Public Figure',
            self::OTHER           => 'Verified',
        };
    }
}
