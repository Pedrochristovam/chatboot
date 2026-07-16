<?php

return [
    'default' => env('WHATSAPP_DRIVER', 'null'),

    'drivers' => [
        'null' => [],
        'meta' => [
            'token' => env('WHATSAPP_META_TOKEN'),
            'phone_number_id' => env('WHATSAPP_META_PHONE_NUMBER_ID'),
            'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        ],
        'evolution' => [
            'base_url' => env('WHATSAPP_EVOLUTION_URL'),
            'api_key' => env('WHATSAPP_EVOLUTION_API_KEY'),
            'instance' => env('WHATSAPP_EVOLUTION_INSTANCE'),
        ],
        'zapi' => [
            'base_url' => env('WHATSAPP_ZAPI_URL'),
            'token' => env('WHATSAPP_ZAPI_TOKEN'),
        ],
        'baileys' => [
            'base_url' => env('WHATSAPP_BAILEYS_URL'),
        ],
    ],
];
