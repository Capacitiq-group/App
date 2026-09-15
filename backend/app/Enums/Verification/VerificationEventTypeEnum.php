<?php

namespace App\Enums\Verification;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum VerificationEventTypeEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case SUBMITTED           = 'submitted';
    case HOLD_PLACED          = 'hold_placed';
    case DIDIT_WEBHOOK        = 'didit_webhook';
    case AUTO_APPROVED        = 'auto_approved';
    case AUTO_REJECTED        = 'auto_rejected';
    case FLAGGED_FOR_REVIEW   = 'flagged_for_review';
    case MANUALLY_APPROVED    = 'manually_approved';
    case MANUALLY_REJECTED    = 'manually_rejected';
    case HOLD_CAPTURED        = 'hold_captured';
    case HOLD_RELEASED        = 'hold_released';
    case HOLD_EXPIRED         = 'hold_expired';
    case PAYMENT_RE_REQUESTED = 'payment_re_requested';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED           => 'Application submitted',
            self::HOLD_PLACED          => 'Payment hold placed',
            self::DIDIT_WEBHOOK        => 'Didit status update received',
            self::AUTO_APPROVED        => 'Automatically approved',
            self::AUTO_REJECTED        => 'Automatically rejected',
            self::FLAGGED_FOR_REVIEW   => 'Flagged for manual review',
            self::MANUALLY_APPROVED    => 'Manually approved',
            self::MANUALLY_REJECTED    => 'Manually rejected',
            self::HOLD_CAPTURED        => 'Payment captured',
            self::HOLD_RELEASED        => 'Payment hold released',
            self::HOLD_EXPIRED         => 'Payment hold expired',
            self::PAYMENT_RE_REQUESTED => 'Payment re-requested',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::SUBMITTED           => 'Đã nộp đơn',
            self::HOLD_PLACED          => 'Đã giữ khoản thanh toán',
            self::DIDIT_WEBHOOK        => 'Nhận cập nhật từ Didit',
            self::AUTO_APPROVED        => 'Đã tự động duyệt',
            self::AUTO_REJECTED        => 'Đã tự động từ chối',
            self::FLAGGED_FOR_REVIEW   => 'Được đánh dấu xem xét thủ công',
            self::MANUALLY_APPROVED    => 'Đã duyệt thủ công',
            self::MANUALLY_REJECTED    => 'Đã từ chối thủ công',
            self::HOLD_CAPTURED        => 'Đã thu tiền',
            self::HOLD_RELEASED        => 'Đã giải phóng khoản giữ',
            self::HOLD_EXPIRED         => 'Khoản giữ đã hết hạn',
            self::PAYMENT_RE_REQUESTED => 'Yêu cầu thanh toán lại',
        };
    }
}
