<?php

namespace App\Enums\Tip;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum TipSourceTypeEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case PROFILE = 'profile';
    case POST    = 'post';
    case SPACE   = 'space';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::PROFILE => 'Profile',
            self::POST    => 'Post',
            self::SPACE   => 'Space',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::PROFILE => 'Hồ sơ',
            self::POST    => 'Bài viết',
            self::SPACE   => 'Không gian âm thanh',
        };
    }
}
