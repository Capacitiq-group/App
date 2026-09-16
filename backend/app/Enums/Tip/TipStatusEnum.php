<?php

namespace App\Enums\Tip;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum TipStatusEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case PENDING   = 'pending';
    case COMPLETED = 'completed';
    case FAILED    = 'failed';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'Pending',
            self::COMPLETED => 'Completed',
            self::FAILED    => 'Failed',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::PENDING   => 'Đang xử lý',
            self::COMPLETED => 'Hoàn tất',
            self::FAILED    => 'Thất bại',
        };
    }
}
