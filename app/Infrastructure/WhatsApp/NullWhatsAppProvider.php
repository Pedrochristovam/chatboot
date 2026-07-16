<?php

namespace Infrastructure\WhatsApp;

use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\DTOs\WhatsApp\OutgoingMessageDTO;
use Application\DTOs\WhatsApp\SendResultDTO;
use Illuminate\Support\Str;

class NullWhatsAppProvider extends AbstractWhatsAppProvider
{
    protected function dispatch(string $type, OutgoingMessageDTO $message): SendResultDTO
    {
        return new SendResultDTO(
            success: true,
            messageId: 'stub_'.Str::uuid()->toString(),
        );
    }

    public function receiveWebhook(array $payload): IncomingMessageDTO
    {
        return $this->normalizeIncoming($payload);
    }
}
