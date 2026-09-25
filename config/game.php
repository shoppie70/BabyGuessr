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
];
