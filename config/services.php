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
        'region' => env('AWS_DEFAULT_REGION', 'eu-north-1'),
        'configuration_set' => env('AWS_SES_CONFIGURATION_SET', ''),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@marketing.thenexteck.com'),
        'from_name' => env('MAIL_FROM_NAME', 'TheNexteck Marketing'),
        'sending_rate' => env('AWS_SES_SENDING_RATE', 60), // emails per minute
        'daily_quota' => env('AWS_SES_DAILY_QUOTA', 50000),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 450),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
