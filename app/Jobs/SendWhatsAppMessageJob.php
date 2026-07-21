<?php

namespace App\Jobs;

use Application\Contracts\WhatsApp\WhatsAppProviderInterface;
use Application\DTOs\WhatsApp\OutgoingMessageDTO;
use Application\Services\Conversation\MessageStatusService;
use Application\Services\WhatsApp\WhatsAppMediaService;
use Domain\Shared\Enums\MessageStatus;
use Domain\Shared\Enums\MessageSenderType;
use Domain\Shared\Enums\MessageType;
use Domain\Shared\Enums\ScheduledMessageStatus;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Storage;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\MessageStatusEvent;
use Infrastructure\Persistence\Eloquent\Models\ScheduledMessage;
use Infrastructure\WhatsApp\MetaCloudProvider;
use Throwable;

class SendWhatsAppMessageJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 20;

    public int $timeout = 120;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 3600;

    public function __construct(public int $messageId) {}

    public function uniqueId(): string
    {
        return 'message:'.$this->messageId;
    }

    public function backoff(): array
    {
        return [5, 15, 30, 60, 120, 300];
    }

    public function middleware(): array
    {
        $message = Message::query()->with('conversation.client')->find($this->messageId);
        $phone = $message?->conversation?->client?->phone;
        $conversationKey = $phone
            ? (preg_replace('/\D+/', '', $phone) ?: $phone)
            : 'message-'.$this->messageId;

        return [
            (new WithoutOverlapping('whatsapp-conversation:'.$conversationKey))
                ->shared()
                ->releaseAfter(3)
                ->expireAfter($this->timeout + 30),
        ];
    }

    public function handle(
        WhatsAppProviderInterface $provider,
        WhatsAppMediaService $mediaService,
        MessageStatusService $statusService,
    ): void
    {
        $message = Message::query()
            ->with(['conversation.client', 'attachments'])
            ->find($this->messageId);

        if (! $message || ! $message->conversation?->client) {
            return;
        }

        $metadata = $message->metadata ?? [];
        if (filled($message->whatsapp_message_id)
            && in_array($message->status, [
                MessageStatus::Sent,
                MessageStatus::Delivered,
                MessageStatus::Read,
            ], true)) {
            $statusService->reconcilePendingForMessage($message);

            return;
        }
        if ($message->status !== MessageStatus::Pending) {
            return;
        }
        if (Message::query()
            ->where('conversation_id', $message->conversation_id)
            ->where('id', '<', $message->id)
            ->where('status', MessageStatus::Pending)
            ->exists()
        ) {
            $this->release(3);

            return;
        }

        if ($message->sender_type === MessageSenderType::Bot) {
            $guard = $metadata['bot_delivery_guard'] ?? [];
            $conversation = $message->conversation;
            $guardValid = $conversation
                && ! ($guard['cancelled'] ?? false)
                && $conversation->assigned_to === null
                && $conversation->status->value === ($guard['expected_status'] ?? null)
                && $conversation->is_bot_handled === ($guard['expects_bot_handled'] ?? null);

            if (! $guardValid) {
                $metadata['bot_delivery_guard'] = array_merge($guard, [
                    'cancelled' => true,
                    'cancelled_reason' => 'ownership_or_status_changed',
                    'cancelled_at' => now()->toIso8601String(),
                ]);
                $message->update([
                    'status' => MessageStatus::Failed,
                    'error_message' => 'Envio do bot cancelado por mudança de responsável ou estado.',
                    'metadata' => $metadata,
                ]);

                return;
            }
        }

        $metadata['delivery'] = array_merge($metadata['delivery'] ?? [], [
            'attempts' => max(
                (int) ($metadata['delivery']['attempts'] ?? 0) + 1,
                $this->attempts(),
            ),
            'last_attempt_at' => now()->toIso8601String(),
            'last_error' => null,
        ]);
        $message->update(['metadata' => $metadata]);

        $attachment = $message->attachments->first();
        $dto = new OutgoingMessageDTO(
            to: $message->conversation->client->phone,
            content: $message->content ?? '',
            mediaUrl: null,
            mediaType: $attachment?->mime_type,
            fileName: $attachment?->file_name,
            metadata: $metadata,
        );

        try {
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
            } elseif ($message->type === MessageType::Template) {
                $result = $provider->sendTemplate($dto);
            } else {
                $result = $provider->sendMessage($dto);
            }
        } catch (Throwable $exception) {
            $metadata['delivery']['last_error'] = mb_substr($exception->getMessage(), 0, 65535);
            $metadata['delivery']['last_failed_at'] = now()->toIso8601String();
            $message->update([
                'status' => MessageStatus::Pending,
                'error_message' => $metadata['delivery']['last_error'],
                'metadata' => $metadata,
            ]);

            throw $exception;
        }

        $metadata['delivery']['last_error'] = $result->error;
        $metadata['delivery']['completed_at'] = now()->toIso8601String();

        $message->update([
            'status' => $result->success ? MessageStatus::Sent : MessageStatus::Failed,
            'whatsapp_message_id' => $result->messageId,
            'sent_at' => $result->success ? now() : $message->sent_at,
            'error_message' => $result->success ? null : ($result->error ?? 'Falha no envio'),
            'metadata' => $metadata,
        ]);

        if ($scheduledId = ($metadata['scheduled_message_id'] ?? null)) {
            ScheduledMessage::query()->whereKey($scheduledId)->update([
                'status' => ($result->success ? ScheduledMessageStatus::Sent : ScheduledMessageStatus::Failed)->value,
                'sent_at' => $result->success ? now() : null,
                'error_message' => $result->success ? null : ($result->error ?? 'Falha no envio'),
            ]);
        }

        MessageStatusEvent::query()->create([
            'message_id' => $message->id,
            'status' => $result->success ? MessageStatus::Sent->value : MessageStatus::Failed->value,
            'provider_event_id' => $result->messageId,
            'payload' => [
                'error' => $result->error,
                'attempt' => $metadata['delivery']['attempts'],
            ],
            'occurred_at' => now(),
            'created_at' => now(),
        ]);

        if ($result->success) {
            $statusService->reconcilePendingForMessage($message->fresh());
        }
    }

    public function failed(?Throwable $exception): void
    {
        $message = Message::query()->find($this->messageId);
        if (! $message) {
            return;
        }

        $metadata = $message->metadata ?? [];
        $metadata['delivery'] = array_merge($metadata['delivery'] ?? [], [
            'last_error' => mb_substr($exception?->getMessage() ?? 'Job failed.', 0, 65535),
            'failed_at' => now()->toIso8601String(),
            'exhausted' => true,
        ]);

        $message->update([
            'status' => MessageStatus::Failed,
            'error_message' => $metadata['delivery']['last_error'],
            'metadata' => $metadata,
        ]);

        if ($scheduledId = ($metadata['scheduled_message_id'] ?? null)) {
            ScheduledMessage::query()->whereKey($scheduledId)->update([
                'status' => ScheduledMessageStatus::Failed->value,
                'error_message' => $metadata['delivery']['last_error'],
            ]);
        }
    }
}
