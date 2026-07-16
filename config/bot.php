<?php

return [
    'enabled' => env('BOT_ENABLED', true),

    'name' => env('BOT_NAME', 'Assistente MGI chat'),

    'ask_name_message' => "Olá! 👋 Sou o assistente virtual do *MGI chat*.\n\nPara começar, qual é o seu *nome*?",

    // Use {name} onde o nome do cliente deve aparecer.
    'welcome_back_message' => "Que bom ter você de volta, *{name}*! 😊\n\nO que posso ajudar hoje?",

    'greeting_keywords' => [
        'oi', 'olá', 'ola', 'oie', 'oii', 'bom dia', 'boa tarde', 'boa noite',
        'hello', 'hey', 'eai', 'e aí', 'e ai', 'fala', 'salve',
    ],

    'human_transfer_keywords' => [
        'atendente',
        'humano',
        'pessoa',
        'operador',
        'suporte humano',
        'falar com alguém',
        'falar com alguem',
        'quero atendimento',
        'atendimento humano',
    ],

    'close_keywords' => [
        'obrigado',
        'obrigada',
        'valeu',
        'resolvido',
        'era só isso',
        'era so isso',
        'só isso',
        'so isso',
        'tchau',
        'até mais',
        'ate mais',
    ],

    'transfer_message' => 'Entendido! Vou transferir você para um de nossos atendentes. Por favor, aguarde um momento. 🙋',

    'closed_message' => 'Fico feliz em ter ajudado! Se precisar de algo mais, é só enviar uma nova mensagem. Até logo!',

    'fallback_message' => 'Não tenho certeza sobre isso. Digite *menu* para ver os assuntos ou *atendente* para falar com um humano.',

    'auto_escalate_on_unknown' => env('BOT_AUTO_ESCALATE', true),
];
