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

    'zeptomail' => [
        'token' => env('ZEPTOMAIL_TOKEN'),
        'endpoint' => env('ZEPTOMAIL_ENDPOINT', 'https://api.zeptomail.com/v1.1/email'),
        'from_address' => env('ZEPTOMAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
        'from_name' => env('ZEPTOMAIL_FROM_NAME', env('MAIL_FROM_NAME', env('APP_NAME', 'Lemonwares'))),
        'logo_url' => env('ZEPTOMAIL_LOGO_URL'),
    ],

    'flutterwave' => [
        'public_key' => env('FLW_PUBLIC_KEY'),
        'secret_key' => env('FLW_SECRET_KEY'),
        'secret_hash' => env('FLW_SECRET_HASH'),
    ],

    'trekmail' => [
        'token' => env('TREKMAIL_API_TOKEN'),
        'base_url' => rtrim(env('TREKMAIL_BASE_URL', 'https://trekmail.net/api/v1'), '/'),
        'webmail_url' => env('TREKMAIL_WEBMAIL_URL', 'https://trekmail.net/webmail'),
    ],

    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
    ],

    'hetzner' => [
        'api_token' => env('HETZNER_API_TOKEN'),
    ],

    'google' => [
        'places_api_key' => env('GOOGLE_PLACES_API_KEY'),
        'place_id' => env('GOOGLE_PLACE_ID'),
        'place_query' => env('GOOGLE_PLACE_QUERY', 'LemonWares Technology Lagos'),
        'business_url' => env('GOOGLE_BUSINESS_URL', 'https://share.google/wqlwtQwIpQUx4b70S'),
        'kg_mid' => env('GOOGLE_KG_MID', '/g/11lsrw_gqc'),
    ],

];
