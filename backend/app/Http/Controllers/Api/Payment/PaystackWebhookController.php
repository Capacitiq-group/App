<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaystackTransactionService;
use App\Services\Space\SpaceTicketService;
use App\Services\Tip\TipService;
use App\Services\Verification\PaystackPreauthService;
use App\Services\Verification\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private readonly PaystackPreauthService $preauth,
        private readonly VerificationService $verificationService,
        private readonly TipService $tipService,
        private readonly SpaceTicketService $ticketService,
    ) {}

    /**
     * Single entry point for every Paystack event on the platform — Paystack
     * only lets you configure one webhook URL per account, so this has to
     * fan out by event name rather than each feature owning its own route.
     * New paid features add a new match arm here, not a new webhook route.
     */
    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('x-paystack-signature');

        // Signature verification is identical for every event on this
        // account (same secret, same algorithm) — using PaystackPreauthService's
        // implementation here is just picking one; PaystackTransactionService's
        // would give the same answer.
        if (! $this->preauth->verifyWebhookSignature($request->getContent(), $signature)) {
            Log::warning('Paystack webhook signature verification failed', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $reference = $request->input('data.reference');

        if ($reference === null) {
            return response()->json(['received' => true]);
        }

        try {
            match ($event) {
                'preauthorization.capture.success' => $this->verificationService->handleCaptureWebhookConfirmation($reference),
                'preauthorization.capture.failed' => $this->verificationService->handleCaptureFailedAfterApproval($reference),
                'charge.success' => $this->handleChargeSuccess($reference),
                default => null,
            };
        } catch (\Throwable $e) {
            // Same principle as the Didit webhook handler: log and still
            // return 2xx rather than trigger a retry storm for an event that
            // will fail identically on redelivery. Paystack does retry
            // failed webhook deliveries.
            Log::error('Failed to process Paystack webhook', [
                'event' => $event,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['received' => true]);
    }

    /**
     * charge.success fires for every plain one-time Transaction on the
     * account — tips and Space tickets both use this API, distinguished
     * only by their reference prefix ('tip_' vs 'ticket_'). Routing by
     * prefix avoids querying the wrong table twice; both services also
     * no-op safely on an unrecognized reference regardless.
     */
    private function handleChargeSuccess(string $reference): void
    {
        match (true) {
            Str::startsWith($reference, 'tip_') => $this->tipService->completeTip($reference),
            Str::startsWith($reference, 'ticket_') => $this->ticketService->completePurchase($reference),
            default => Log::info('charge.success for an unrecognized reference prefix', ['reference' => $reference]),
        };
    }
}
