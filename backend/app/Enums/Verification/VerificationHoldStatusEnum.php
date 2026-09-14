<?php

namespace App\Enums\Verification;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum VerificationHoldStatusEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case HELD     = 'held';
    case CAPTURED = 'captured';
    case RELEASED = 'released';
    case EXPIRED  = 'expired';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::HELD     => 'Held',
            self::CAPTURED => 'Captured',
            self::RELEASED => 'Released',
            self::EXPIRED  => 'Expired',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::HELD     => 'Đang giữ',
            self::CAPTURED => 'Đã thu',
            self::RELEASED => 'Đã giải phóng',
            self::EXPIRED  => 'Đã hết hạn',
        };
    }
}
