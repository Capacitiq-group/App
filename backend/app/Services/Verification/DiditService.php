<?php

namespace App\Services\Verification;

use App\Enums\Verification\VerificationApplicantTypeEnum;
use App\Exceptions\http\BusinessException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * These are Didit's "no-code" hosted workflow links (Section 4.3 of the
 * platform rules doc) — one static URL per applicant type, configured once
 * in the Didit console, not created per-session via API. Our job is just to
 * append vendor_data (our application's uuid) so it round-trips back in the
 * webhook payload, letting us match a decision to the right application
 * without needing the didit_session_id upfront.
 */
class DiditService
{
    /**
     * Build the URL to send an applicant to for their verification session.
     *
     * @throws BusinessException If no workflow URL is configured for this type.
     */
    public function buildVerificationUrl(VerificationApplicantTypeEnum $applicantType, string $applicationUuid): string
    {
        $workflowUrl = config("didit.workflow_urls.{$applicantType->value}");

        if (empty($workflowUrl)) {
            throw new BusinessException("No Didit workflow configured for applicant type: {$applicantType->value}");
        }

        $separator = str_contains($workflowUrl, '?') ? '&' : '?';

        return $workflowUrl.$separator.'vendor_data='.urlencode($applicationUuid);
    }

    /**
     * Verify a Didit webhook's signature against the raw request body.
     * Checks both possible header names — see config/didit.php's comment on
     * why there are two.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = (string) config('didit.webhook_secret');
        $rawBody = $request->getContent();

        foreach ((array) config('didit.webhook_signature_headers') as $headerName) {
            $signature = $request->header($headerName);

            if ($signature === null) {
                continue;
            }

            $expected = hash_hmac('sha256', $rawBody, $secret);

            if (hash_equals($expected, (string) $signature)) {
                return $this->timestampIsFresh($request);
            }
        }

        return false;
    }

    /**
     * Replay protection: reject webhooks whose timestamp header is older
     * than the configured max age.
     */
    private function timestampIsFresh(Request $request): bool
    {
        $timestampHeader = (string) config('didit.webhook_timestamp_header');
        $timestamp = $request->header($timestampHeader);

        if ($timestamp === null) {
            // No timestamp header present — can't check freshness, so don't
            // fail the whole verification over it (signature already matched).
            return true;
        }

        $maxAge = (int) config('didit.webhook_max_age_seconds');

        return abs(time() - (int) $timestamp) <= $maxAge;
    }

    /**
     * Fetch the full decision report for a session directly from Didit —
     * used by the reconciliation job, and as a fallback if a webhook payload
     * doesn't carry everything needed (e.g. the matched-officer detail for
     * a business/KYB session).
     *
     * @return array<string, mixed>
     *
     * @throws BusinessException
     */
    public function fetchDecision(string $sessionId): array
    {
        $response = Http::baseUrl((string) config('didit.api_base_url'))
            ->withHeaders(['x-api-key' => config('didit.api_key')])
            ->acceptJson()
            ->get("/session/{$sessionId}/decision/");

        if ($response->failed()) {
            throw new BusinessException('Failed to fetch Didit decision: '.$response->status());
        }

        return $response->json();
    }
}
