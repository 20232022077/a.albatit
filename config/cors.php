<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The site has no API for another origin to legitimately call — every
    | request comes from the same origin via the web routes. Left
    | unpublished, this file falls back to the framework's permissive
    | package default (allowed_origins: ['*']), which becomes a real
    | misconfiguration the moment anyone adds an api/* route later without
    | revisiting it. Locked to nothing allowed until an API is deliberately
    | introduced and this is scoped to specific trusted origins.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
