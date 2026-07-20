<?php

namespace Infrastructure\WhatsApp;

use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\DTOs\WhatsApp\OutgoingMessageDTO;
use Application\DTOs\WhatsApp\SendResultDTO;
use Application\Services\WhatsApp\WhatsAppConfigService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MetaCloudProvider extends AbstractWhatsAppProvider
{
    public function __construct(
        private readonly WhatsAppConfigService $config,
    ) {}

    protected function dispatch(string $type, OutgoingMessageDTO $message): SendResultDTO
    {
        $token = $this->config->metaToken();
        $phoneNumberId = $this->config->metaPhoneNumberId();

        if (! $token || ! $phoneNumberId) {
            return new SendResultDTO(
                success: false,
                error: 'Configure o Token e o Phone Number ID em Configurações → WhatsApp Meta.',
            );
        }

        $to = preg_replace('/\D/', '', $message->to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
        ];

        $interactive = $message->metadata['interactive'] ?? null;

        if (is_array($interactive) && ! empty($interactive['type'])) {
            $payload['type'] = 'interactive';
            $payload['interactive'] = $interactive;
        } elseif ($type === 'image') {
            $imagePayload = [];
            if (! empty($message->metadata['media_id'])) {
                $imagePayload['id'] = $message->metadata['media_id'];
            } elseif ($message->mediaUrl) {
                $imagePayload['link'] = $message->mediaUrl;
            } else {
                return new SendResultDTO(success: false, error: 'Imagem sem URL ou media_id.');
            }

            if (filled($message->content) && $message->content !== '[imagem]') {
                $imagePayload['caption'] = $message->content;
            }

            $payload['type'] = 'image';
            $payload['image'] = $imagePayload;
        } elseif ($type === 'document') {
            $doc = [];
            if (! empty($message->metadata['media_id'])) {
                $doc['id'] = $message->metadata['media_id'];
            } elseif ($message->mediaUrl) {
                $doc['link'] = $message->mediaUrl;
            } else {
                return new SendResultDTO(success: false, error: 'Documento sem URL ou media_id.');
            }
            if ($message->fileName) {
                $doc['filename'] = $message->fileName;
            }
            if (filled($message->content)) {
                $doc['caption'] = $message->content;
            }
            $payload['type'] = 'document';
            $payload['document'] = $doc;
        } else {
            $payload['type'] = 'text';
            $payload['text'] = ['body' => $message->content ?? ''];
        }

        try {
            $response = Http::withToken($token)
                ->connectTimeout(10)
                ->timeout(30)
                ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", $payload);
        } catch (ConnectionException $exception) {
            throw new TransientProviderException(
                'Não foi possível conectar à API da Meta.',
                previous: $exception,
            );
        }

        if ($response->successful()) {
            $id = $response->json('messages.0.id') ?? Str::uuid()->toString();

            return new SendResultDTO(success: true, messageId: $id);
        }

        $this->throwIfTransient($response);

        if (isset($payload['interactive'])) {
            try {
                $fallback = Http::withToken($token)
                    ->connectTimeout(10)
                    ->timeout(30)
                    ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                        'messaging_product' => 'whatsapp',
                        'to' => $to,
                        'type' => 'text',
                        'text' => ['body' => $message->content ?? ''],
                    ]);
            } catch (ConnectionException $exception) {
                throw new TransientProviderException(
                    'Não foi possível conectar à API da Meta.',
                    previous: $exception,
                );
            }

            if ($fallback->successful()) {
                return new SendResultDTO(
                    success: true,
                    messageId: $fallback->json('messages.0.id') ?? Str::uuid()->toString(),
                );
            }

            $this->throwIfTransient($fallback);
        }

        return new SendResultDTO(
            success: false,
            error: $response->json('error.message') ?? $response->body(),
        );
    }

    /**
     * Faz upload da mídia para a Meta e retorna o media_id.
     */
    public function uploadMedia(string $absolutePath, string $mimeType): ?string
    {
        $token = $this->config->metaToken();
        $phoneNumberId = $this->config->metaPhoneNumberId();

        if (! $token || ! $phoneNumberId || ! is_readable($absolutePath)) {
            return null;
        }

        $response = Http::withToken($token)
            ->attach('file', file_get_contents($absolutePath), basename($absolutePath))
            ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/media", [
                'messaging_product' => 'whatsapp',
                'type' => $mimeType,
            ]);

        if (! $response->successful()) {
            return null;
        }

        return $response->json('id');
    }

    public function fetchMedia(?string $mediaId = null, ?string $mediaUrl = null): ?array
    {
        $token = $this->config->metaToken();
        if (! $token) {
            return null;
        }

        $downloadUrl = $mediaUrl;
        $mime = null;

        if ($mediaId) {
            $meta = Http::withToken($token)
                ->get("https://graph.facebook.com/v21.0/{$mediaId}");

            if (! $meta->successful()) {
                return null;
            }

            $downloadUrl = $meta->json('url');
            $mime = $meta->json('mime_type');
        }

        if (! $downloadUrl) {
            return null;
        }

        $binary = Http::withToken($token)
            ->withHeaders(['User-Agent' => 'MGI-chat'])
            ->get($downloadUrl);

        if (! $binary->successful()) {
            return null;
        }

        return [
            'contents' => $binary->body(),
            'mime_type' => $mime ?: $binary->header('Content-Type'),
            'file_name' => null,
        ];
    }

    public function receiveWebhook(array $payload): IncomingMessageDTO
    {
        if (isset($payload['entry'])) {
            return $this->parseWebhook($payload)['messages'][0]
                ?? new IncomingMessageDTO(from: '', messageId: '', type: 'text', content: null);
        }

        return $this->normalizeIncoming($payload);
    }

    /**
     * @return array{
     *     messages: list<IncomingMessageDTO>,
     *     statuses: list<array<string, mixed>>
     * }
     */
    public function parseWebhook(array $payload): array
    {
        $messages = [];
        $statuses = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $message) {
                    if (is_array($message)) {
                        $messages[] = $this->parseMetaMessage($value, $message);
                    }
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    if (is_array($status)) {
                        $statuses[] = $status;
                    }
                }
            }
        }

        return ['messages' => $messages, 'statuses' => $statuses];
    }

    private function parseMetaMessage(array $value, array $message): IncomingMessageDTO
    {
        $type = $message['type'] ?? 'text';
        $interactiveId = $message['interactive']['button_reply']['id']
            ?? $message['interactive']['list_reply']['id']
            ?? $message['button']['payload']
            ?? null;
        $interactiveTitle = $message['interactive']['button_reply']['title']
            ?? $message['interactive']['list_reply']['title']
            ?? $message['button']['text']
            ?? null;

        $mediaId = $message['image']['id']
            ?? $message['document']['id']
            ?? $message['audio']['id']
            ?? $message['video']['id']
            ?? $message['sticker']['id']
            ?? null;

        $mime = $message['image']['mime_type']
            ?? $message['document']['mime_type']
            ?? $message['audio']['mime_type']
            ?? $message['video']['mime_type']
            ?? $message['sticker']['mime_type']
            ?? null;

        $fileName = $message['document']['filename'] ?? null;

        $content = match ($type) {
            'text' => $message['text']['body'] ?? null,
            'button' => $interactiveTitle ?? $interactiveId,
            'interactive' => $interactiveTitle ?? $interactiveId,
            'image' => $message['image']['caption'] ?? '[imagem]',
            'document' => $message['document']['caption'] ?? ($fileName ?: '[documento]'),
            'audio' => '[áudio]',
            'video' => $message['video']['caption'] ?? '[vídeo]',
            'sticker' => '[figurinha]',
            default => "[{$type}]",
        };

        $contactName = collect($value['contacts'] ?? [])
            ->firstWhere('wa_id', $message['from'])['profile']['name']
            ?? $value['contacts'][0]['profile']['name']
            ?? null;

        $normalizedType = in_array($type, ['button', 'interactive'], true) ? 'text' : $type;

        return new IncomingMessageDTO(
            from: $message['from'] ?? '',
            messageId: $message['id'] ?? Str::uuid()->toString(),
            type: $normalizedType,
            content: $content,
            mediaUrl: null,
            mediaMimeType: $mime,
            fileName: $fileName,
            mediaId: $mediaId,
            metadata: [
                'contact_name' => $contactName,
                'pushName' => $contactName,
                'interactive_id' => $interactiveId,
                'interactive_title' => $interactiveTitle,
            ],
        );
    }

    private function throwIfTransient(Response $response): void
    {
        $errorCode = (int) ($response->json('error.code') ?? 0);
        $isTransient = (bool) ($response->json('error.is_transient') ?? false);

        if ($response->status() === 429
            || $response->serverError()
            || $isTransient
            || in_array($errorCode, [1, 2, 4, 17, 32, 341, 613], true)) {
            throw new TransientProviderException(
                $response->json('error.message') ?: 'Falha temporária na API da Meta.'
            );
        }
    }
}
