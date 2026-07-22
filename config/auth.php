<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'supabase'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | The default session guard is retained (so the whole app is protected by a
    | normal server-side Laravel session), but it is backed by the Supabase
    | user provider instead of Eloquent.
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'supabase',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Identity is owned by Supabase Auth (GoTrue). The custom "supabase" driver
    | (registered in App\Providers\AppServiceProvider) rehydrates the signed-in
    | user from the server-side session snapshot; credentials themselves are
    | verified against Supabase during the login request.
    |
    */

    'providers' => [
        'supabase' => [
            'driver' => 'supabase',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Password resets are delegated to Supabase Auth (recovery / magic-link
    | emails), so Laravel's built-in broker is intentionally left empty.
    |
    */

    'passwords' => [],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
