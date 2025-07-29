<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    
    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
        'auth_token' => env('N8N_AUTH_TOKEN'),
        'webhook_urls' => [
            'english' => env('N8N_WEBHOOK_URL_ENGLISH', env('N8N_WEBHOOK_URL')),
            'arabic' => env('N8N_WEBHOOK_URL_ARABIC'),
            'french' => env('N8N_WEBHOOK_URL_FRENCH'),
        ],
    ],

    'baserow' => [
        'api_url' => env('BASEROW_API_URL', 'https://api.baserow.io'),
        'database_token' => env('BASEROW_DATABASE_TOKEN'),
        'table_id' => env('BASEROW_TABLE_ID'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
];
