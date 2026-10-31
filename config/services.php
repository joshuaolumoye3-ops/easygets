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

    'afrikvend' => [
        'base_url' => env('AFRIKVEND_BASE_URL', 'https://api.afrikvend.com'),
        'api_key'  => env('AFRIKVEND_API_KEY'),
    ],
    'morethanpanel' => [
        'api_url' => env('MORETHANPANEL_API_URL', 'https://morethanpanel.com/api/v2'),
        'api_key' => env('MORETHANPANEL_API_KEY'),
    ],
    'flutterwave' => [
        'base_url'    => env('FLW_BASE_URL', 'https://api.flutterwave.com/v3'),
        'secret_key'  => env('FLW_SECRET_KEY'),
        'public_key'  => env('FLW_PUBLIC_KEY'),
        'secret_hash' => env('FLW_SECRET_HASH'),
        'admin_alert_email'            => env('PAYOUT_ADMIN_EMAIL'),
        'payout_fee_buffer'            => (float) env('FLW_PAYOUT_FEE_BUFFER', 100),
        'max_single_payout'            => (float) env('FLW_MAX_SINGLE_PAYOUT', 1000000),
        'payout_auto'                  => (bool) env('FLW_PAYOUT_AUTO', true),
        'payout_require_name_match'    => (bool) env('FLW_PAYOUT_REQUIRE_NAME_MATCH', false),
    ],
        'korapay' => [
        'public_key'        => env('KORAPAY_PUBLIC_KEY'),
        'secret_key'        => env('KORAPAY_SECRET_KEY'),
        'base_url'          => env('KORAPAY_BASE_URL', 'https://api.korapay.com/merchant/api/v1'),
        // Default settlement bank for newly created dedicated accounts.
        // See https://developers.korapay.com/docs/virtual-bank-accounts-ngn for the
        // current list of supported banks/codes. Use "000" only in sandbox.
        'default_bank_code' => env('KORAPAY_DEFAULT_BANK_CODE', '035'),
    ],
    'fivesmm' => [
        'api_url' => env('FIVESMM_API_URL', 'https://5smm.com/api/v2'),
        'api_key' => env('FIVESMM_API_KEY'),
    ],
    '5sim' => [
        'token' => env('FIVESIM_TOKEN'),
    ],

    'smsbower' => [
        'api_key' => env('SMSBOWER_API_KEY'),
    ],

    'smsbus' => [
        'token' => env('SMSBUS_TOKEN'),
    ],

    'prestmit' => [
        'base_url'        => env('PRESTMIT_BASE_URL', 'https://dev-api.prestmit.io/partners/v1'),
        'api_key'         => env('PRESTMIT_API_KEY'),
        'api_secret'      => env('PRESTMIT_API_SECRET'),
        'webhook_secret'  => env('PRESTMIT_WEBHOOK_SECRET'),
        'account_pin'     => env('PRESTMIT_ACCOUNT_PIN'),
        'timeout'         => 30,
        'empty_hash_body' => env('PRESTMIT_EMPTY_HASH_BODY', '[]'),
        'hash_numeric'    => filter_var(env('PRESTMIT_HASH_NUMERIC', false), FILTER_VALIDATE_BOOLEAN),
    ],

];