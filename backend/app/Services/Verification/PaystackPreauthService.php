<?php

namespace App\Services\Verification;

use App\Exceptions\http\BusinessException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Wraps Paystack's Preauthorization API (South Africa only): hold an amount
 * on a card without charging it, then later capture (charge) or release
 * (charge nothing) that hold.
 *
 * NOTE ON ENDPOINT PATHS: Paystack's public docs describe this API's
 * behavior and parameters in detail, but this class's exact path strings
 * (/preauthorization/initialize, /capture, /release, /:reference) are
 * reconstructed from that documented behavior rather than copied from a
 * literal, confirmed API reference — verify each path against Paystack's
 * current dashboard/API reference before this goes live, the same way you'd
 * sanity-check any third-party integration before production.
 */
class PaystackPreauthService
{
    private string $baseUrl;
    private string $secretKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('paystack.base_url'), '/');
        $this->secretKey = (string) config('paystack.secret_key');
    }

    /**
     * Place a preauthorization hold for the verification fee. Returns the
     * Paystack reference and the hosted checkout URL to redirect the
     * applicant to.
     *
     * @return array{reference: string, authorization_url: string, access_code: string}
     *
     * @throws BusinessException On a non-2xx response from Paystack.
     */
    public function initializeHold(string $email, string $callbackUrl, ?string $reference = null): array
    {
        $reference ??= 'verify_'.Str::uuid()->toString();

        $response = $this->client()->post('/preauthorization/initialize', [
            'email' => $email,
            'amount' => (int) config('paystack.verification.fee_cents'),
            'currency' => config('paystack.currency'),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'expire_action' => config('paystack.verification.hold_expire_action'),
            'expires_in_days' => (int) config('paystack.verification.hold_expire_days'),
        ]);

        $this->assertSuccessful($response, 'initialize preauthorization hold');

        $data = $response->json('data');

        return [
            'reference' => $reference,
            'authorization_url' => $data['authorization_url'],
            'access_code' => $data['access_code'],
        ];
    }

    /**
     * Capture (actually charge) a previously-held amount. Called only after
     * Didit's webhook confirms approval — never speculatively.
     *
     * @throws BusinessException
     */
    public function capture(string $reference, ?int $amountCents = null): void
    {
        $response = $this->client()->post('/preauthorization/capture', array_filter([
            'reference' => $reference,
            'amount' => $amountCents,
            'currency' => config('paystack.currency'),
        ]));

        $this->assertSuccessful($response, 'capture preauthorization');
    }

    /**
     * Release a hold without charging anything. Called on rejection, or by
     * the reconciliation job if a hold is about to expire with no decision.
     *
     * @throws BusinessException
     */
    public function release(string $reference): void
    {
        $response = $this->client()->post('/preauthorization/release', [
            'reference' => $reference,
        ]);

        $this->assertSuccessful($response, 'release preauthorization hold');
    }

    /**
     * Fetch the current status of a preauthorized transaction directly from
     * Paystack — used by the reconciliation job rather than trusting only
     * locally-stored state, since a webhook can be missed.
     *
     * @return array{status: string, ...}
     *
     * @throws BusinessException
     */
    public function fetchStatus(string $reference): array
    {
        $response = $this->client()->get("/preauthorization/{$reference}");

        $this->assertSuccessful($response, 'fetch preauthorization status');

        return $response->json('data');
    }

    /**
     * Verify a Paystack webhook's signature. Raw body is required —
     * Paystack signs the exact bytes sent, not a re-serialized JSON parse.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if ($signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $expected = hash_hmac('sha512', $rawBody, (string) config('paystack.webhook_secret'));

        return hash_equals($expected, $signatureHeader);
    }

    private function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->acceptJson();
    }

    /**
     * @throws BusinessException
     */
    private function assertSuccessful($response, string $action): void
    {
        if ($response->failed()) {
            throw new BusinessException(
                "Failed to {$action}: ".($response->json('message') ?? $response->status())
            );
        }
    }
}
