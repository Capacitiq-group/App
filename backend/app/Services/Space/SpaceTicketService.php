<?php

namespace App\Services\Space;

use App\Enums\Tip\TipStatusEnum;
use App\Exceptions\http\BusinessException;
use App\Models\Space;
use App\Models\SpaceTicket;
use App\Models\User;
use App\Repositories\SpaceTicketRepository;
use App\Services\Payment\PaystackTransactionService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpaceTicketService
{
    public function __construct(
        private readonly SpaceTicketRepository $tickets,
        private readonly PaystackTransactionService $paystack,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Start a ticket purchase. Returns the pending ticket and the Paystack
     * checkout URL.
     *
     * @return array{ticket: SpaceTicket, authorization_url: string}
     *
     * @throws BusinessException
     */
    public function purchase(Space $space, User $buyer, string $callbackUrl): array
    {
        if ($this->tickets->hasCompletedTicket($space, $buyer->id)) {
            throw new BusinessException('You already have a ticket for this Space.');
        }

        $priceCents = $space->ticket_price_cents;
        $feePercentage = (int) config('agora.tickets.platform_fee_percentage');
        $platformFeeCents = (int) round($priceCents * $feePercentage / 100);
        $hostAmountCents = $priceCents - $platformFeeCents;

        return DB::transaction(function () use ($space, $buyer, $priceCents, $platformFeeCents, $hostAmountCents, $callbackUrl) {
            $ticket = $this->tickets->create([
                'space_id' => $space->id,
                'buyer_id' => $buyer->id,
                'amount_cents' => $priceCents,
                'platform_fee_cents' => $platformFeeCents,
                'host_amount_cents' => $hostAmountCents,
                'status' => TipStatusEnum::PENDING->value,
                'paystack_reference' => 'pending_'.uniqid('', true),
            ]);

            $charge = $this->paystack->initialize(
                email: $buyer->email,
                amountCents: $priceCents,
                callbackUrl: $callbackUrl,
                reference: 'ticket_'.$ticket->uuid,
            );

            $ticket->update(['paystack_reference' => $charge['reference']]);

            return ['ticket' => $ticket->refresh(), 'authorization_url' => $charge['authorization_url']];
        });
    }

    /**
     * Complete a ticket purchase after Paystack confirms payment.
     * Idempotent — safe to call more than once for the same reference.
     */
    public function completePurchase(string $reference): void
    {
        $ticket = $this->tickets->findByReference($reference);

        if ($ticket === null) {
            return; // not a ticket reference — likely a tip or verification charge.
        }

        if ($ticket->status !== TipStatusEnum::PENDING) {
            return;
        }

        $verified = $this->paystack->verify($reference);

        if (($verified['status'] ?? null) !== 'success') {
            $ticket->update(['status' => TipStatusEnum::FAILED->value]);

            return;
        }

        if ((int) ($verified['amount'] ?? 0) !== $ticket->amount_cents) {
            Log::critical('Paystack verified amount does not match the ticket record — not crediting automatically', [
                'space_ticket_id' => $ticket->id,
                'expected_amount_cents' => $ticket->amount_cents,
                'verified_amount_cents' => $verified['amount'] ?? null,
            ]);

            return;
        }

        DB::transaction(function () use ($ticket) {
            $ticket->update(['status' => TipStatusEnum::COMPLETED->value]);

            $this->wallet->credit(
                user: $ticket->space->host,
                amountCents: $ticket->host_amount_cents,
                type: 'space_ticket_sold',
                referenceType: 'space_ticket',
                referenceId: $ticket->id,
            );
        });
    }
}
