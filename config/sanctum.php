<?php

use Laravel\Sanctum\Sanctum;

return [


    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort()
    ))),


    'guard' => ['web'],


    // Minutes until a device token expires. Left null by default so existing
    // offline devices are not cut off, but set SANCTUM_EXPIRATION (e.g. 43200
    // for 30 days) in production once the device client can re-authenticate.
    'expiration' => env('SANCTUM_EXPIRATION'),


    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],

];
