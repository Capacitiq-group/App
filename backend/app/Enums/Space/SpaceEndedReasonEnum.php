<?php

namespace App\Enums\Space;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum SpaceEndedReasonEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case HOST_ENDED        = 'host_ended';
    case TIMER_EXPIRED     = 'timer_expired';
    case BELOW_MINIMUM     = 'below_minimum';
    case HOST_LEFT         = 'host_left';
    case AUTO_CLOSED_EMPTY = 'auto_closed_empty';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::HOST_ENDED        => 'Ended by host',
            self::TIMER_EXPIRED     => 'Duration limit reached',
            self::BELOW_MINIMUM     => 'Fell below minimum participants',
            self::HOST_LEFT         => 'Host left',
            self::AUTO_CLOSED_EMPTY => 'Closed automatically (never activated)',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::HOST_ENDED        => 'Chủ phòng đã kết thúc',
            self::TIMER_EXPIRED     => 'Đã hết thời gian',
            self::BELOW_MINIMUM     => 'Không đủ số người tối thiểu',
            self::HOST_LEFT         => 'Chủ phòng đã rời đi',
            self::AUTO_CLOSED_EMPTY => 'Tự động đóng (chưa từng bắt đầu)',
        };
    }
}
