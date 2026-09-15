<?php

return [
    'secret_key' => env('PAYSTACK_SECRET_KEY', ''),
    'public_key' => env('PAYSTACK_PUBLIC_KEY', ''),
    'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    'currency' => env('PAYSTACK_CURRENCY', 'ZAR'),

    // Paystack webhook signature — sent in the x-paystack-signature header,
    // HMAC-SHA512 of the raw request body using the secret key.
    'webhook_secret' => env('PAYSTACK_SECRET_KEY', ''),

    'verification' => [
        // R79.00 in cents — Paystack amounts are always the smallest currency unit.
        'fee_cents' => (int) env('VERIFICATION_FEE_CENTS', 7900),

        // Hold window: "confirm the amount before the hold expires". Default
        // 5 days per the platform rules doc; NEVER auto-capture on expiry —
        // expire_action must stay 'release' so an un-reviewed application
        // never gets silently charged.
        'hold_expire_days' => (int) env('VERIFICATION_HOLD_EXPIRE_DAYS', 5),
        'hold_expire_action' => 'release',
    ],
];
