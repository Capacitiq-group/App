<?php

namespace App\Services\Wallet;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Credit a user's wallet and record the ledger entry in one transaction.
     * $amountCents must be positive — this is a credit-only method; a debit
     * (once payouts exist) is a separate method, not a negative call here.
     */
    public function credit(User $user, int $amountCents, string $type, ?string $referenceType = null, ?int $referenceId = null): void
    {
        if ($amountCents <= 0) {
            throw new \InvalidArgumentException('Wallet credit amount must be positive.');
        }

        DB::transaction(function () use ($user, $amountCents, $type, $referenceType, $referenceId) {
            // Lock the row so two concurrent credits (e.g. two tips landing
            // at the same moment) can't both read the same starting balance.
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();
            $newBalance = $lockedUser->wallet_balance_cents + $amountCents;

            $lockedUser->update(['wallet_balance_cents' => $newBalance]);

            $lockedUser->walletTransactions()->create([
                'amount_cents' => $amountCents,
                'balance_after_cents' => $newBalance,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        });
    }
}
