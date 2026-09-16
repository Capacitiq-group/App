<?php

namespace App\Services\Tip;

use App\Exceptions\http\BusinessException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaystackTransactionService
{
    private string $baseUrl;
    private string $secretKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('paystack.base_url'), '/');
        $this->secretKey = (string) config('paystack.secret_key');
    }

    /**
     * Initialize a one-time charge. Returns the reference and hosted
     * checkout URL to redirect the tipper to.
     *
     * @return array{reference: string, authorization_url: string, access_code: string}
     *
     * @throws BusinessException
     */
    public function initialize(string $email, int $amountCents, string $callbackUrl, ?string $reference = null): array
    {
        $reference ??= 'tip_'.Str::uuid()->toString();

        $response = Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->acceptJson()
            ->post('/transaction/initialize', [
                'email' => $email,
                'amount' => $amountCents,
                'currency' => config('paystack.currency'),
                'reference' => $reference,
                'callback_url' => $callbackUrl,
            ]);

        if ($response->failed()) {
            throw new BusinessException('Failed to initialize payment: '.($response->json('message') ?? $response->status()));
        }

        $data = $response->json('data');

        return [
            'reference' => $reference,
            'authorization_url' => $data['authorization_url'],
            'access_code' => $data['access_code'],
        ];
    }

    /**
     * Verify a transaction's actual status directly with Paystack —
     * required before crediting anything, never trust a webhook payload's
     * amount/status alone. See Paystack's own guidance: "Always re-query
     * ... before providing the customer with any value."
     *
     * @return array{status: string, amount: int, reference: string}
     *
     * @throws BusinessException
     */
    public function verify(string $reference): array
    {
        $response = Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->acceptJson()
            ->get("/transaction/verify/{$reference}");

        if ($response->failed()) {
            throw new BusinessException('Failed to verify transaction: '.$response->status());
        }

        return $response->json('data');
    }

    /**
     * Verify a Paystack webhook's signature — identical algorithm to
     * PaystackPreauthService's, duplicated here rather than shared because
     * these two classes cover genuinely different Paystack APIs and
     * shouldn't depend on each other for something this small.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if ($signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $expected = hash_hmac('sha512', $rawBody, (string) config('paystack.webhook_secret'));

        return hash_equals($expected, $signatureHeader);
    }
}
