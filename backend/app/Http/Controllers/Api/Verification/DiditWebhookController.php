<?php

namespace App\Http\Controllers\Api\Verification;

use App\Http\Controllers\Controller;
use App\Services\Verification\DiditService;
use App\Services\Verification\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DiditWebhookController extends Controller
{
    public function __construct(
        private readonly DiditService $didit,
        private readonly VerificationService $verificationService,
    ) {}

    /**
     * Didit retries a failed delivery up to 5 times with exponential
     * backoff, then drops it permanently — so this must reliably return a
     * 2xx even if something downstream is slow, and processing failures
     * should be logged rather than surfaced as a 5xx that triggers a retry
     * storm for an event that will just fail the same way again.
     */
    public function handle(Request $request): JsonResponse
    {
        if (! $this->didit->verifyWebhookSignature($request)) {
            Log::warning('Didit webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = $request->json()->all();

        try {
            $this->verificationService->handleDiditWebhook($payload);
        } catch (\Throwable $e) {
            Log::error('Failed to process Didit webhook', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            // Still 200 — see class docblock. The reconciliation job catches
            // anything that falls through a processing failure like this.
        }

        return response()->json(['received' => true]);
    }
}
