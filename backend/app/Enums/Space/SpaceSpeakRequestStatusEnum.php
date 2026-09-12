<?php

namespace App\Enums\Space;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum SpaceSpeakRequestStatusEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case PENDING   = 'pending';
    case ACCEPTED  = 'accepted';
    case DECLINED  = 'declined';
    case CANCELLED = 'cancelled';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'Pending',
            self::ACCEPTED  => 'Accepted',
            self::DECLINED  => 'Declined',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::PENDING   => 'Đang chờ',
            self::ACCEPTED  => 'Đã chấp nhận',
            self::DECLINED  => 'Đã từ chối',
            self::CANCELLED => 'Đã hủy',
        };
    }

    /**
     * Whether this status is final (no longer actionable).
     */
    public function isResolved(): bool
    {
        return $this !== self::PENDING;
    }
}
