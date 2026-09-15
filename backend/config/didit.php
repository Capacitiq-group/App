<?php

return [
    'api_key' => env('DIDIT_API_KEY', ''),
    'api_base_url' => env('DIDIT_API_BASE_URL', 'https://verification.didit.me/v3'),

    // Webhook signature verification. Didit's own documentation is
    // inconsistent about the exact header name across sources — some say
    // X-Signature, some say X-Didit-Signature. Confirm the actual header in
    // your Didit dashboard's webhook config before going live; both are
    // checked here so either works without a code change.
    'webhook_secret' => env('DIDIT_WEBHOOK_SECRET', ''),
    'webhook_signature_headers' => ['X-Signature', 'X-Didit-Signature'],
    'webhook_timestamp_header' => 'X-Timestamp',
    // Reject a webhook whose timestamp is older than this — replay protection.
    'webhook_max_age_seconds' => (int) env('DIDIT_WEBHOOK_MAX_AGE_SECONDS', 300),

    // No-code hosted workflow links, one per applicant type (Section 4.3 of
    // the platform rules doc — three separate workflows, not one with branches).
    'workflow_urls' => [
        'individual' => env('DIDIT_WORKFLOW_URL_INDIVIDUAL', ''),
        'business' => env('DIDIT_WORKFLOW_URL_BUSINESS', ''),
        'political_entity' => env('DIDIT_WORKFLOW_URL_POLITICAL', ''),
    ],
];
