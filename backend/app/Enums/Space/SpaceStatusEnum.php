<?php

namespace App\Enums\Space;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum SpaceStatusEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case WAITING = 'waiting';
    case LIVE    = 'live';
    case ENDED   = 'ended';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::WAITING => 'Waiting for participants',
            self::LIVE    => 'Live',
            self::ENDED   => 'Ended',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::WAITING => 'Đang chờ người tham gia',
            self::LIVE    => 'Đang diễn ra',
            self::ENDED   => 'Đã kết thúc',
        };
    }
}
