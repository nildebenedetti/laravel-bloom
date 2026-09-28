<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // the SPA is only ever allowed to need
    // GET, POST, PUT, PATCH, DELETE and OPTIONS. Allowing everything also permits methods
    // no route implements, which is harmless in practice but signals an unaudited default.
    
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],


    'allowed_origins' => [
        'http://localhost:5173',
    ],


    'allowed_origins_patterns' => [],

    // Allowed_headers ['*'] is a deliberate choice for a token API, but
    // it means the preflight accepts any custom header. Narrowing to
    // ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With', 'X-XSRF-TOKEN']
    // costs nothing and makes the API surface legible.
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With', 'X-XSRF-TOKEN'],

    'exposed_headers' => ['X-Total-Count', 'Link'],

    'max_age' => 0,

    // supports_credentials true is REQUIRED here, because Sanctum's
    // cookie flow sends the XSRF-TOKEN cookie. Combined with allowed_headers ['*'] it
    // is a wide surface, so the hardcoded origin in 16b is the real risk: anyone who can
    // change that array to '*' silently turns on full credentialed cross-origin access.
    // Fix: keep credentials on, but make the origin list env-driven so the wildcard is
    //      a decision someone had to write down in .env.
    'supports_credentials' => true,

];
