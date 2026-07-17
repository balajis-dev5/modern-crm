<?php

return [

    /*
     * Secret used to sign HS256 access tokens. MUST be set in production;
     * defaults to the app key so local setup is zero-config.
     */
    'secret' => env('JWT_SECRET', env('APP_KEY')),

    // Access tokens are short-lived on purpose: a leaked one dies in 15 minutes.
    'access_ttl' => env('JWT_ACCESS_TTL', 60 * 15),

    // Refresh tokens live in the DB and rotate on every use (30 days).
    'refresh_ttl' => env('JWT_REFRESH_TTL', 60 * 60 * 24 * 30),

];
