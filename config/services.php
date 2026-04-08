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
        'token' => env('POSTMARK_TOKEN'),
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

    'monnify' => [
        'api_key' => env('MONNIFY_API_KEY'),
        'secret_key' => env('MONNIFY_SECRET_KEY'),
        'contract_code' => env('MONNIFY_CONTRACT_CODE'),
        'is_live' => env('MONNIFY_IS_LIVE', false),
    ],

    'vtuafrica' => [
        'api_key' => env('VTU_AFRICA_API_KEY'),
    ],

    'demmy' => [
        'base_url'    => env('DEMMY_BASE_URL', 'https://apis.demmypayservices.com.ng'),
        'api_key'     => env('DEMMY_API_KEY'),
        'pass_phrase' => env('DEMMY_PASS_PHRASE'),
    ],
    'morethanpanel' => [
        'api_url' => env('MORETHANPANEL_API_URL', 'https://morethanpanel.com/api/v2'),
        'api_key' => env('MORETHANPANEL_API_KEY'),
    ],
    'flutterwave' => [
        'base_url' => trim(env('FLW_BASE_URL', 'https://api.flutterwave.com/v3')),
        'secret_hash' => trim(env('FLW_SECRET_HASH', '')),
        'public_key' => trim(env('FLW_PUBLIC_KEY', '')),
        'secret_key' => trim(env('FLW_SECRET_KEY', '')),
    ]

];