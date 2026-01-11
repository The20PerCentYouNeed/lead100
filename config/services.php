<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'firecrawl' => [
        'api_key' => env('FIRECRAWL_API_KEY'),
        'api_url' => env('FIRECRAWL_API_URL', 'https://api.firecrawl.dev/v2'),
    ],

    'rapidapi' => [
        'key' => env('RAPIDAPI_KEY'),
        'linkedin_host' => env(
            'RAPIDAPI_LINKEDIN_HOST',
            'linkedin-data-api.p.rapidapi.com'
        ),
    ],

    'website_context' => [
        'cache_ttl_days' => env('WEBSITE_CONTEXT_CACHE_TTL_DAYS', 7),
    ],

];
