<?php

namespace App\Jobs;

use Application\Contracts\WhatsApp\WhatsAppProviderInterface;
use Application\DTOs\WhatsApp\OutgoingMessageDTO;
use Application\Services\WhatsApp\WhatsAppMediaService;
use Domain\Shared\Enums\MessageStatus;
use Domain\Shared\Enums\MessageType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\WhatsApp\MetaCloudProvider;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $messageId) {}

    public function handle(WhatsAppProviderInterface $provider, WhatsAppMediaService $mediaService): void
    {
        $message = Message::query()
            ->with(['conversation.client', 'attachments'])
            ->find($this->messageId);

        if (! $message || ! $message->conversation?->client) {
            return;
        }

        $metadata = $message->metadata ?? [];
        $attachment = $message->attachments->first();
        $dto = new OutgoingMessageDTO(
            to: $message->conversation->client->phone,
            content: $message->content ?? '',
            mediaUrl: null,
            mediaType: $attachment?->mime_type,
            fileName: $attachment?->file_name,
            metadata: $metadata,
        );

        if ($attachment && in_array($message->type, [MessageType::Image, MessageType::Document], true)) {
            // Preferir upload na Meta (funciona sem URL pública); fallback para link público
            if ($provider instanceof MetaCloudProvider) {
                $absolute = Storage::disk('public')->path($attachment->file_path);
                $mediaId = $provider->uploadMedia($absolute, $attachment->mime_type);
                if ($mediaId) {
                    $dto = new OutgoingMessageDTO(
                        to: $dto->to,
                        content: $dto->content,
                        mediaUrl: null,
                        mediaType: $attachment->mime_type,
                        fileName: $attachment->file_name,
                        metadata: array_merge($metadata, ['media_id' => $mediaId]),
                    );
                } else {
                    $dto = new OutgoingMessageDTO(
                        to: $dto->to,
                        content: $dto->content,
                        mediaUrl: $mediaService->absolutePublicUrl($attachment),
                        mediaType: $attachment->mime_type,
                        fileName: $attachment->file_name,
                        metadata: $metadata,
                    );
                }
            } else {
                $dto = new OutgoingMessageDTO(
                    to: $dto->to,
                    content: $dto->content,
                    mediaUrl: $mediaService->absolutePublicUrl($attachment)
                        ?? Storage::disk('public')->path($attachment->file_path),
                    mediaType: $attachment->mime_type,
                    fileName: $attachment->file_name,
                    metadata: $metadata,
                );
            }

            $result = $message->type === MessageType::Document
                ? $provider->sendDocument($dto)
                : $provider->sendImage($dto);
        } else {
            $result = $provider->sendMessage($dto);
        }

        $message->update([
            'status' => $result->success ? MessageStatus::Sent : MessageStatus::Failed,
            'whatsapp_message_id' => $result->messageId,
            'sent_at' => $result->success ? now() : $message->sent_at,
            'error_message' => $result->success ? null : ($result->error ?? 'Falha no envio'),
            'metadata' => array_merge($metadata, ['error' => $result->error]),
        ]);

        if ($result->success || $result->error) {
            \Infrastructure\Persistence\Eloquent\Models\MessageStatusEvent::query()->create([
                'message_id' => $message->id,
                'status' => $result->success ? MessageStatus::Sent->value : MessageStatus::Failed->value,
                'provider_event_id' => $result->messageId,
                'payload' => ['error' => $result->error],
                'occurred_at' => now(),
                'created_at' => now(),
            ]);
        }
    }
}
