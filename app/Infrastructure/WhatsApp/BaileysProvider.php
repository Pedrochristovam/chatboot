<?php

namespace Infrastructure\WhatsApp;

use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\DTOs\WhatsApp\OutgoingMessageDTO;
use Application\DTOs\WhatsApp\SendResultDTO;

class BaileysProvider extends AbstractWhatsAppProvider
{
    protected function dispatch(string $type, OutgoingMessageDTO $message): SendResultDTO
    {
        return new SendResultDTO(success: false, error: 'Baileys não configurado.');
    }

    public function receiveWebhook(array $payload): IncomingMessageDTO
    {
        return $this->normalizeIncoming($payload);
    }
}
