<?php

namespace Infrastructure\WhatsApp;

use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\DTOs\WhatsApp\OutgoingMessageDTO;
use Application\DTOs\WhatsApp\SendResultDTO;

class ZApiProvider extends AbstractWhatsAppProvider
{
    protected function dispatch(string $type, OutgoingMessageDTO $message): SendResultDTO
    {
        return new SendResultDTO(success: false, error: 'Z-API não configurada.');
    }

    public function receiveWebhook(array $payload): IncomingMessageDTO
    {
        return $this->normalizeIncoming($payload);
    }
}
