<?php

namespace App\Enums\Space;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum SpaceTypeEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case PUBLIC       = 'public';
    case PRIVATE      = 'private';
    case MEETING_ROOM  = 'meeting_room';
    case TOPIC          = 'topic';
    case CREATOR        = 'creator';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::PUBLIC      => 'Public',
            self::PRIVATE     => 'Private',
            self::MEETING_ROOM => 'Meeting Room',
            self::TOPIC         => 'Topic Space',
            self::CREATOR       => 'Creator Space',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::PUBLIC      => 'Công khai',
            self::PRIVATE     => 'Riêng tư',
            self::MEETING_ROOM => 'Phòng họp',
            self::TOPIC         => 'Không gian chủ đề',
            self::CREATOR       => 'Không gian người sáng tạo',
        };
    }

    /**
     * Whether joining requires an explicit invitation — the only type-level
     * access control enforced today. The other four types are all
     * categorization/discovery labels; behaviour beyond this gate (e.g.
     * Meeting Rooms defaulting to a smaller cap) isn't implemented yet.
     */
    public function requiresInvitation(): bool
    {
        return $this === self::PRIVATE;
    }

    /**
     * Whether this type is eligible to sell tickets. Only Creator Spaces can
     * be ticketed, and only when hosted by a verified user — see
     * SpaceService::assertCanHost().
     */
    public function canBeTicketed(): bool
    {
        return $this === self::CREATOR;
    }
}
