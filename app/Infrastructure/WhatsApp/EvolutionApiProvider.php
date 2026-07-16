<?php

namespace Infrastructure\WhatsApp;

use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\DTOs\WhatsApp\OutgoingMessageDTO;
use Application\DTOs\WhatsApp\SendResultDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EvolutionApiProvider extends AbstractWhatsAppProvider
{
    protected function dispatch(string $type, OutgoingMessageDTO $message): SendResultDTO
    {
        $baseUrl = rtrim(config('whatsapp.drivers.evolution.base_url', ''), '/');
        $apiKey = config('whatsapp.drivers.evolution.api_key');
        $instance = config('whatsapp.drivers.evolution.instance');

        if (! $baseUrl || ! $apiKey || ! $instance) {
            return new SendResultDTO(success: false, error: 'Evolution API: configure WHATSAPP_EVOLUTION_URL, API_KEY e INSTANCE no .env');
        }

        $number = $this->normalizePhone($message->to);

        $response = Http::withHeaders(['apikey' => $apiKey])
            ->post("{$baseUrl}/message/sendText/{$instance}", [
                'number' => $number,
                'text' => $message->content,
            ]);

        if ($response->successful()) {
            $id = $response->json('key.id') ?? $response->json('messageId') ?? Str::uuid()->toString();

            return new SendResultDTO(success: true, messageId: $id);
        }

        return new SendResultDTO(
            success: false,
            error: $response->json('message') ?? $response->body(),
        );
    }

    public function receiveWebhook(array $payload): IncomingMessageDTO
    {
        if (isset($payload['data']['key'])) {
            return $this->parseEvolutionPayload($payload);
        }

        return $this->normalizeIncoming($payload);
    }

    private function parseEvolutionPayload(array $payload): IncomingMessageDTO
    {
        $data = $payload['data'];
        $key = $data['key'] ?? [];

        if ($key['fromMe'] ?? false) {
            return new IncomingMessageDTO(from: '', messageId: '', type: 'text', content: null);
        }

        $jid = $key['remoteJid'] ?? '';
        $from = $this->normalizePhone(str_replace('@s.whatsapp.net', '', $jid));

        $message = $data['message'] ?? [];
        $content = $message['conversation']
            ?? $message['extendedTextMessage']['text']
            ?? $message['imageMessage']['caption']
            ?? null;

        $type = match (true) {
            isset($message['imageMessage']) => 'image',
            isset($message['documentMessage']) => 'document',
            isset($message['audioMessage']) => 'audio',
            default => 'text',
        };

        return new IncomingMessageDTO(
            from: $from,
            messageId: $key['id'] ?? Str::uuid()->toString(),
            type: $type,
            content: $content,
            mediaUrl: $data['message']['imageMessage']['url'] ?? null,
            metadata: $payload,
        );
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone) ?? $phone;
    }
}
