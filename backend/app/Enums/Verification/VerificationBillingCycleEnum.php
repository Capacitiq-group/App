<?php

namespace App\Enums\Verification;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum VerificationBillingCycleEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case MONTHLY = 'monthly';
    case ANNUAL  = 'annual';

    /**
     * Get the label of the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monthly',
            self::ANNUAL  => 'Annual',
        };
    }

    /**
     * Get the translated label of the enum value.
     */
    public function translate(): string
    {
        return match ($this) {
            self::MONTHLY => 'Hàng tháng',
            self::ANNUAL  => 'Hàng năm',
        };
    }

    /**
     * The fee for this cycle, in cents — R99/month or R999/year.
     */
    public function feeCents(): int
    {
        return match ($this) {
            self::MONTHLY => (int) config('paystack.verification.monthly_fee_cents'),
            self::ANNUAL  => (int) config('paystack.verification.annual_fee_cents'),
        };
    }

    /**
     * The configured Paystack plan code for this cycle's recurring subscription.
     */
    public function paystackPlanCode(): string
    {
        return (string) config("paystack.verification.plan_codes.{$this->value}");
    }
}
