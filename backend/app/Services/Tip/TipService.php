<?php

namespace App\Services\Tip;

use App\Enums\Tip\TipSourceTypeEnum;
use App\Enums\Tip\TipStatusEnum;
use App\Exceptions\http\BusinessException;
use App\Models\Tip;
use App\Models\User;
use App\Repositories\TipRepository;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TipService
{
    public function __construct(
        private readonly TipRepository $tips,
        private readonly PaystackTransactionService $paystack,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Start a tip. Returns the tip row (pending) and the Paystack checkout
     * URL to redirect the tipper to.
     *
     * @return array{tip: Tip, authorization_url: string}
     *
     * @throws BusinessException
     */
    public function createTip(
        User $tipper,
        User $recipient,
        int $amountCents,
        TipSourceTypeEnum $sourceType,
        ?int $sourceId,
        ?string $message,
        string $callbackUrl,
    ): array {
        if ($tipper->id === $recipient->id) {
            throw new BusinessException('You cannot tip yourself.');
        }

        $minAmount = (int) config('paystack.tips.min_amount_cents');

        if ($amountCents < $minAmount) {
            throw new BusinessException('Minimum tip is R'.number_format($minAmount / 100, 2).'.');
        }

        $feePercentage = (int) config('paystack.tips.platform_fee_percentage');
        $platformFeeCents = (int) round($amountCents * $feePercentage / 100);
        $recipientAmountCents = $amountCents - $platformFeeCents;

        return DB::transaction(function () use ($tipper, $recipient, $amountCents, $platformFeeCents, $recipientAmountCents, $sourceType, $sourceId, $message, $callbackUrl) {
            $tip = $this->tips->create([
                'tipper_id' => $tipper->id,
                'recipient_id' => $recipient->id,
                'source_type' => $sourceType->value,
                'source_id' => $sourceId,
                'amount_cents' => $amountCents,
                'platform_fee_cents' => $platformFeeCents,
                'recipient_amount_cents' => $recipientAmountCents,
                'status' => TipStatusEnum::PENDING->value,
                'message' => $message,
                // Placeholder — replaced right after Paystack confirms the
                // reference below, so the unique constraint has something
                // valid from the first insert.
                'paystack_reference' => 'pending_'.uniqid('', true),
            ]);

            $charge = $this->paystack->initialize(
                email: $tipper->email,
                amountCents: $amountCents,
                callbackUrl: $callbackUrl,
                reference: 'tip_'.$tip->uuid,
            );

            $tip->update(['paystack_reference' => $charge['reference']]);

            return ['tip' => $tip->refresh(), 'authorization_url' => $charge['authorization_url']];
        });
    }

    /**
     * Complete a tip after Paystack confirms payment. Re-verifies directly
     * with Paystack rather than trusting the webhook payload's amount/status
     * — idempotent, safe to call more than once for the same reference.
     */
    public function completeTip(string $reference): void
    {
        $tip = $this->tips->findByReference($reference);

        if ($tip === null) {
            Log::warning('Paystack charge.success for a reference with no matching tip', ['reference' => $reference]);

            return;
        }

        if ($tip->status !== TipStatusEnum::PENDING) {
            return; // already completed or failed — duplicate webhook, no-op.
        }

        $verified = $this->paystack->verify($reference);

        if (($verified['status'] ?? null) !== 'success') {
            $tip->update(['status' => TipStatusEnum::FAILED->value]);

            return;
        }

        // Integrity check: the amount actually charged must match what we
        // computed the split from. A mismatch here means something is wrong
        // enough to need a human, not an auto-credit.
        if ((int) ($verified['amount'] ?? 0) !== $tip->amount_cents) {
            Log::critical('Paystack verified amount does not match the tip record — not crediting automatically', [
                'tip_id' => $tip->id,
                'expected_amount_cents' => $tip->amount_cents,
                'verified_amount_cents' => $verified['amount'] ?? null,
            ]);

            return;
        }

        DB::transaction(function () use ($tip) {
            $tip->update(['status' => TipStatusEnum::COMPLETED->value]);

            $this->wallet->credit(
                user: $tip->recipient,
                amountCents: $tip->recipient_amount_cents,
                type: 'tip_received',
                referenceType: 'tip',
                referenceId: $tip->id,
            );
        });
    }
}
