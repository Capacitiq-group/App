<?php

namespace App\Enums\Space;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum SpaceDiscoveryScopeEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case PUBLIC    = 'public';
    case FOLLOWERS = 'followers';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::PUBLIC    => 'Public',
            self::FOLLOWERS => 'Followers only',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::PUBLIC    => 'Công khai',
            self::FOLLOWERS => 'Chỉ người theo dõi',
        };
    }
}
