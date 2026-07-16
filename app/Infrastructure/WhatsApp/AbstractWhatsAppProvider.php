<?php

namespace Infrastructure\WhatsApp;

use Application\Contracts\WhatsApp\WhatsAppProviderInterface;
use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\DTOs\WhatsApp\OutgoingMessageDTO;
use Application\DTOs\WhatsApp\SendResultDTO;
use Illuminate\Support\Str;

abstract class AbstractWhatsAppProvider implements WhatsAppProviderInterface
{
    public function sendMessage(OutgoingMessageDTO $message): SendResultDTO
    {
        return $this->dispatch('text', $message);
    }

    public function sendImage(OutgoingMessageDTO $message): SendResultDTO
    {
        return $this->dispatch('image', $message);
    }

    public function sendDocument(OutgoingMessageDTO $message): SendResultDTO
    {
        return $this->dispatch('document', $message);
    }

    public function sendAudio(OutgoingMessageDTO $message): SendResultDTO
    {
        return $this->dispatch('audio', $message);
    }

    public function sendLocation(OutgoingMessageDTO $message): SendResultDTO
    {
        return $this->dispatch('location', $message);
    }

    /**
     * @return array{contents: string, mime_type?: string, file_name?: string}|null
     */
    public function fetchMedia(?string $mediaId = null, ?string $mediaUrl = null): ?array
    {
        if (! $mediaUrl) {
            return null;
        }

        if (is_file($mediaUrl) && is_readable($mediaUrl)) {
            return [
                'contents' => (string) file_get_contents($mediaUrl),
                'mime_type' => mime_content_type($mediaUrl) ?: null,
                'file_name' => basename($mediaUrl),
            ];
        }

        if (str_starts_with($mediaUrl, 'http://') || str_starts_with($mediaUrl, 'https://')) {
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($mediaUrl);
            if (! $response->successful()) {
                return null;
            }

            return [
                'contents' => $response->body(),
                'mime_type' => $response->header('Content-Type'),
                'file_name' => basename(parse_url($mediaUrl, PHP_URL_PATH) ?: 'arquivo'),
            ];
        }

        // Caminho relativo no disco public
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($mediaUrl)) {
            return [
                'contents' => \Illuminate\Support\Facades\Storage::disk('public')->get($mediaUrl),
                'mime_type' => \Illuminate\Support\Facades\Storage::disk('public')->mimeType($mediaUrl) ?: null,
                'file_name' => basename($mediaUrl),
            ];
        }

        return null;
    }

    abstract protected function dispatch(string $type, OutgoingMessageDTO $message): SendResultDTO;

    protected function normalizeIncoming(array $payload): IncomingMessageDTO
    {
        return new IncomingMessageDTO(
            from: $payload['from'] ?? '',
            messageId: $payload['id'] ?? Str::uuid()->toString(),
            type: $payload['type'] ?? 'text',
            content: $payload['content'] ?? null,
            mediaUrl: $payload['media_url'] ?? null,
            mediaMimeType: $payload['mime_type'] ?? null,
            fileName: $payload['file_name'] ?? null,
            mediaId: $payload['media_id'] ?? null,
            metadata: $payload,
        );
    }
}
