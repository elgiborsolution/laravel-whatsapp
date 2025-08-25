<?php

return [
    'routes_middleware' => ['api'],
    'routes_prefix' => 'whatsapp',

    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'change-me'),

    'single_account' => [
        'enabled' => env('WHATSAPP_SINGLE_ACCOUNT', false),
        'name' => env('WHATSAPP_NAME', 'default'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'waba_id' => env('WHATSAPP_WABA_ID'),
    ],

    'broadcast' => [
        'chunk_size' => env('WHATSAPP_BROADCAST_CHUNK', 1000),
        'rate_per_min' => env('WHATSAPP_BROADCAST_RPM', 3000),
        'max_retries' => 3,
        'retry_delay_seconds' => 30,
    ],

    'queue' => env('WHATSAPP_QUEUE', null),

    'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com/v23.0'),
];
