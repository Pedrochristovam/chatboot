<?php

return [
    'default' => env('AI_DRIVER', 'null'),

    'drivers' => [
        'null' => [],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
    ],
];
