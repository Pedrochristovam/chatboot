<?php

namespace Application\Contracts\WhatsApp;

use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\DTOs\WhatsApp\OutgoingMessageDTO;
use Application\DTOs\WhatsApp\SendResultDTO;

interface WhatsAppProviderInterface
{
    public function sendMessage(OutgoingMessageDTO $message): SendResultDTO;

    public function sendImage(OutgoingMessageDTO $message): SendResultDTO;

    public function sendDocument(OutgoingMessageDTO $message): SendResultDTO;

    public function sendAudio(OutgoingMessageDTO $message): SendResultDTO;

    public function sendLocation(OutgoingMessageDTO $message): SendResultDTO;

    public function sendTemplate(OutgoingMessageDTO $message): SendResultDTO;

    /**
     * Baixa mídia do provedor.
     *
     * @return array{contents: string, mime_type?: string, file_name?: string}|null
     */
    public function fetchMedia(?string $mediaId = null, ?string $mediaUrl = null): ?array;

    public function receiveWebhook(array $payload): IncomingMessageDTO;
}
