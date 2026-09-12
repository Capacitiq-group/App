<?php

namespace App\Enums\Space;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum SpaceParticipantRoleEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case HOST     = 'host';
    case CO_HOST  = 'co_host';
    case SPEAKER  = 'speaker';
    case LISTENER = 'listener';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::HOST     => 'Host',
            self::CO_HOST  => 'Co-host',
            self::SPEAKER  => 'Speaker',
            self::LISTENER => 'Listener',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::HOST     => 'Chủ phòng',
            self::CO_HOST  => 'Đồng chủ phòng',
            self::SPEAKER  => 'Người nói',
            self::LISTENER => 'Người nghe',
        };
    }

    /**
     * Whether this role can publish audio (host, co-host, and speaker can; listener cannot).
     */
    public function canPublishAudio(): bool
    {
        return $this !== self::LISTENER;
    }

    /**
     * Whether this role can moderate the Space (mute/remove/end/accept speak requests).
     */
    public function canModerate(): bool
    {
        return in_array($this, [self::HOST, self::CO_HOST], true);
    }
}
