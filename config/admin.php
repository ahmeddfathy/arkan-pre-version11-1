<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Basic Authentication Credentials
    |--------------------------------------------------------------------------
    |
    | These credentials are used for basic HTTP authentication to protect
    | admin routes and sensitive areas of the application.
    |
    */

    'basic_auth' => [
        'username' => env('BASIC_AUTH_USERNAME', 'admin'),
        'password' => env('BASIC_AUTH_PASSWORD', 'password'),
    ],
];
