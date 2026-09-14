<?php

namespace App\Enums\Verification;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum VerificationStatusEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case IN_PROGRESS        = 'in_progress';
    case UNDER_REVIEW        = 'under_review';
    case VERIFIED            = 'verified';
    case FAILED              = 'failed';
    case EXTRA_REVIEW_NEEDED = 'extra_review_needed';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::IN_PROGRESS        => 'Verification in progress',
            self::UNDER_REVIEW        => 'Under review',
            self::VERIFIED            => 'Verified',
            self::FAILED              => 'Verification failed',
            self::EXTRA_REVIEW_NEEDED => 'Extra review needed',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::IN_PROGRESS        => 'Đang xác minh',
            self::UNDER_REVIEW        => 'Đang chờ duyệt',
            self::VERIFIED            => 'Đã xác minh',
            self::FAILED              => 'Xác minh thất bại',
            self::EXTRA_REVIEW_NEEDED => 'Cần xem xét thêm',
        };
    }

    /**
     * Whether this status is final — no further automatic transition expected.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::VERIFIED, self::FAILED], true);
    }
}
