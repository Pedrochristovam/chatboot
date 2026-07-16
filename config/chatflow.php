<?php

return [
    'name' => env('CHATFLOW_NAME', 'MGI chat'),
    'primary_color' => env('CHATFLOW_PRIMARY_COLOR', '#8B1E3F'),
    'business_hours' => [
        'start' => '08:00',
        'end' => '18:00',
        'days' => [1, 2, 3, 4, 5],
    ],
    'auto_reply_message' => 'Olá! Recebemos sua mensagem e em breve um atendente irá respondê-lo.',
];
