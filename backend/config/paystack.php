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
        // R99.00/month or R999.00/year, in cents — Paystack amounts are
        // always the smallest currency unit. Annual works out to ~2 months
        // free vs. paying monthly (R1,188/year) — R189 saving.
        'monthly_fee_cents' => (int) env('VERIFICATION_MONTHLY_FEE_CENTS', 9900),
        'annual_fee_cents' => (int) env('VERIFICATION_ANNUAL_FEE_CENTS', 99900),

        // Paystack Plan codes for the *recurring* charge after the first
        // payment succeeds and the application is approved — created once in
        // the Paystack dashboard (or via their Plan API), not per-application.
        'plan_codes' => [
            'monthly' => env('PAYSTACK_VERIFICATION_MONTHLY_PLAN_CODE', ''),
            'annual' => env('PAYSTACK_VERIFICATION_ANNUAL_PLAN_CODE', ''),
        ],

        // Hold window: "confirm the amount before the hold expires". Default
        // 5 days per the platform rules doc; NEVER auto-capture on expiry —
        // expire_action must stay 'release' so an un-reviewed application
        // never gets silently charged.
        'hold_expire_days' => (int) env('VERIFICATION_HOLD_EXPIRE_DAYS', 5),
        'hold_expire_action' => 'release',
    ],
];
