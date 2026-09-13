<?php

return [
    'app_id'          => env('AGORA_APP_ID', ''),
    'app_certificate' => env('AGORA_APP_CERTIFICATE', ''),

    // How long a minted RTC token stays valid. Clients must call the token
    // endpoint again (via renewToken) before this expires — tokens are never
    // stored, only minted on demand.
    'token_expiry_seconds' => (int) env('AGORA_TOKEN_EXPIRY_SECONDS', 3600),

    // V1 Space limits — copied onto each `spaces` row at creation time, so
    // changing these later never retroactively affects Spaces already running.
    'defaults' => [
        'min_to_start'     => (int) env('SPACE_MIN_TO_START', 5),
        'min_to_continue'  => (int) env('SPACE_MIN_TO_CONTINUE', 2),
        'max_participants' => (int) env('SPACE_MAX_PARTICIPANTS', 50),
        'max_speakers'     => (int) env('SPACE_MAX_SPEAKERS', 8),
        'duration_minutes' => (int) env('SPACE_DURATION_MINUTES', 30),
    ],

    // Hosting eligibility / rate limiting.
    'host_min_account_age_days' => (int) env('SPACE_HOST_MIN_ACCOUNT_AGE_DAYS', 7),
    'host_min_public_posts'     => (int) env('SPACE_HOST_MIN_PUBLIC_POSTS', 3),
    'host_min_age_years'        => (int) env('SPACE_HOST_MIN_AGE_YEARS', 18),
    'host_max_per_rolling_week' => (int) env('SPACE_HOST_MAX_PER_ROLLING_WEEK', 3),
];
