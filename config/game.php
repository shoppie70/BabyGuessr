<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Game HMAC Secret Key
    |--------------------------------------------------------------------------
    |
    | Used to generate secure HMAC-SHA256 digests for baby names and readings
    | to allow verification without decrypting sensitive data.
    |
    */
    'hmac_secret' => env('GAME_HMAC_SECRET', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Dedicated Setup Token
    |--------------------------------------------------------------------------
    |
    | Secret token for parents to perform initial one-time profile registration.
    |
    */
    'setup_token' => env('SETUP_TOKEN', 'demo-setup-token-2026'),

    /*
    |--------------------------------------------------------------------------
    | Default Management & Diagnostics Tokens
    |--------------------------------------------------------------------------
    |
    | Used as default tokens when creating profile or inspecting diagnostics
    | even before a profile has been registered.
    |
    */
    'manage_token' => env('MANAGE_TOKEN', 'demo-manage-token-2026'),
    'diagnostics_token' => env('DIAGNOSTICS_TOKEN', 'demo-diag-token-2026'),
];
