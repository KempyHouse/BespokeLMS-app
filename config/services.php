<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supabase
    |--------------------------------------------------------------------------
    |
    | BespokeLMS uses Supabase Auth (GoTrue) as its single identity provider
    | and Supabase Postgres (via PostgREST, RLS-scoped) as its data source.
    | Only the publishable "anon" key is safe to expose to the browser; the
    | service-role key must stay server-side and is optional here.
    |
    */

    'supabase' => [
        'url' => rtrim((string) env('SUPABASE_URL', ''), '/'),
        'anon_key' => env('SUPABASE_ANON_KEY'),
        'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'redirect_url' => env('SUPABASE_REDIRECT_URL'),
        'timeout' => (int) env('SUPABASE_HTTP_TIMEOUT', 10),
    ],

];
