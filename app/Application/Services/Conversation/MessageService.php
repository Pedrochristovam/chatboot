<?php

namespace Application\Services\Conversation;

use App\Events\MessageSent;
use App\Events\ConversationUpdated;
use App\Jobs\SendWhatsAppMessageJob;
use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\Services\Bot\BotService;
use Application\Services\WhatsApp\WhatsAppMediaService;
use Domain\Shared\Enums\ClientStatus;
use Domain\Shared\Enums\ConversationStatus;
use Domain\Shared\Enums\MessageSenderType;
use Domain\Shared\Enums\MessageStatus;
use Domain\Shared\Enums\MessageType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\Message;

class MessageService
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly ConversationWorkflowService $workflow,
        private readonly BotService $botService,
        private readonly WhatsAppMediaService $mediaService,
        private readonly SlaService $sla,
    ) {}

    public function sendFromAgent(Conversation $conversation, int $agentId, string $content): Message
    {
        $messageId = DB::transaction(function () use ($conversation, $agentId, $content) {
            $locked = $this->lockForAgentSend($conversation, $agentId);
            $message = Message::query()->create([
                'conversation_id' => $locked->id,
                'sender_type' => MessageSenderType::Agent,
                'sender_id' => $agentId,
                'type' => MessageType::Text,
                'content' => $this->resolveQuickReply($content),
                'status' => MessageStatus::Pending,
            ]);
            $this->touchConversationAfterAgentSend($locked, $agentId);
            SendWhatsAppMessageJob::dispatch($message->id)->afterCommit();
            DB::afterCommit(fn () => event(new ConversationUpdated($locked->id, 'message_sent')));

            return $message->id;
        }, 3);

        $message = Message::query()->with('attachments')->findOrFail($messageId);
        event(new MessageSent($message));

        return $message;
    }

    public function sendImageFromAgent(
        Conversation $conversation,
        int $agentId,
        UploadedFile $file,
        ?string $caption = null,
    ): Message {
        $caption = filled($caption) ? trim($caption) : null;
        $messageId = DB::transaction(function () use ($conversation, $agentId, $file, $caption) {
            $locked = $this->lockForAgentSend($conversation, $agentId);
            $message = Message::query()->create([
                'conversation_id' => $locked->id,
                'sender_type' => MessageSenderType::Agent,
                'sender_id' => $agentId,
                'type' => MessageType::Image,
                'content' => $caption ?: '[imagem]',
                'status' => MessageStatus::Pending,
            ]);
            $this->mediaService->attachUploadedImage($message, $file);
            $this->touchConversationAfterAgentSend($locked, $agentId);
            SendWhatsAppMessageJob::dispatch($message->id)->afterCommit();
            DB::afterCommit(fn () => event(new ConversationUpdated($locked->id, 'message_sent')));

            return $message->id;
        }, 3);

        $message = Message::query()->with('attachments')->findOrFail($messageId);
        event(new MessageSent($message));

        return $message;
    }

    public function processIncoming(IncomingMessageDTO $dto): Message
    {
        if (filled($dto->messageId)) {
            $existing = Message::query()->where('whatsapp_message_id', $dto->messageId)->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($dto) {
            $contactName = $dto->metadata['contact_name'] ?? $dto->metadata['pushName'] ?? null;
            $normalizedPhone = preg_replace('/\D+/', '', $dto->from) ?: $dto->from;

            $identity = DB::table('client_phone_identities')
                ->where('normalized_phone', $normalizedPhone)
                ->lockForUpdate()
                ->first();

            $client = $identity
                ? Client::withTrashed()->find($identity->canonical_client_id)
                : Client::query()
                    ->where('phone_normalized', $normalizedPhone)
                    ->orWhere('phone', $dto->from)
                    ->lockForUpdate()
                    ->first();

            if (! $client) {
                $client = Client::query()->create([
                    'phone' => $dto->from,
                    'name' => $contactName ?? $dto->from,
                    'whatsapp_name' => $contactName,
                    'status' => ClientStatus::Active,
                    'source' => 'whatsapp',
                    'phone_normalized' => $normalizedPhone,
                ]);
            } elseif ($client->trashed()) {
                $client->restore();
            }

            DB::table('client_phone_identities')->insertOrIgnore([
                'normalized_phone' => $normalizedPhone,
                'canonical_client_id' => $client->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        if ($contactName) {
            $updates = [];
            if (! $client->whatsapp_name) {
                $updates['whatsapp_name'] = $contactName;
            }
            if ($client->name === $client->phone || $client->name === $dto->from) {
                $updates['name'] = $contactName;
            }
            if ($updates) {
                $client->update($updates);
            }
        }

        $botEnabled = $this->botService->isEnabled();
        $conversation = $this->conversationService->findOrCreateForClient($client, $botEnabled);
        $isNewConversation = $conversation->wasRecentlyCreated;

        // Metadata enxuta (não grava o webhook inteiro)
        $safeMeta = array_filter([
            'contact_name' => $dto->metadata['contact_name'] ?? null,
            'pushName' => $dto->metadata['pushName'] ?? null,
            'interactive_id' => $dto->metadata['interactive_id'] ?? null,
            'interactive_title' => $dto->metadata['interactive_title'] ?? null,
            'media_id' => $dto->mediaId,
        ], fn ($v) => $v !== null && $v !== '');

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => MessageSenderType::Client,
            'sender_id' => $client->id,
            'type' => $this->resolveMessageType($dto->type),
            'content' => $dto->content,
            'whatsapp_message_id' => $dto->messageId,
            'status' => MessageStatus::Delivered,
            'metadata' => $safeMeta,
        ]);

        $this->mediaService->attachFromIncoming($message, $dto);

        if ($conversation->status === ConversationStatus::BotActive) {
            $conversation->update(['last_message_at' => now()]);
        } else {
            $conversation->update([
                'last_message_at' => now(),
                'unread_count' => $conversation->unread_count + 1,
            ]);
        }

        $client->update(['last_contact_at' => now()]);

        if ($conversation->status === ConversationStatus::BotActive) {
            if ($isNewConversation || empty(($conversation->metadata['bot']['step'] ?? null))) {
                $this->botService->startConversation($conversation->fresh());
            } else {
                $this->botService->handleIncoming($message->fresh(['attachments']), $conversation->fresh());
            }
        }

            return $message->fresh(['attachments']);
        }, 3);
    }

    /** @deprecated Use processIncoming() */
    public function receiveFromWebhook(IncomingMessageDTO $dto): Message
    {
        return $this->processIncoming($dto);
    }

    public function markAsRead(Conversation $conversation): void
    {
        $conversation->update(['unread_count' => 0]);
        $conversation->messages()
            ->where('sender_type', MessageSenderType::Client)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function messagesForConversation(Conversation $conversation, ?int $beforeId = null, int $limit = 50): array
    {
        $limit = max(10, min(100, $limit));

        $query = $conversation->messages()
            ->with('attachments')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->limit($limit + 1)->get();
        $hasMore = $messages->count() > $limit;
        if ($hasMore) {
            $messages = $messages->take($limit);
        }

        $serialized = $messages
            ->sortBy([
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->map(fn (Message $m) => $this->serializeMessage($m))
            ->all();

        return [
            'messages' => $serialized,
            'has_more' => $hasMore,
            'next_before_id' => $hasMore ? $messages->min('id') : null,
        ];
    }

    public function messageCounts(Conversation $conversation): array
    {
        $counts = $conversation->messages()
            ->selectRaw('sender_type, COUNT(*) as total')
            ->groupBy('sender_type')
            ->pluck('total', 'sender_type');

        return [
            'client_messages' => (int) ($counts[MessageSenderType::Client->value] ?? 0),
            'bot_messages' => (int) ($counts[MessageSenderType::Bot->value] ?? 0),
            'agent_messages' => (int) ($counts[MessageSenderType::Agent->value] ?? 0),
        ];
    }

    public function serializeMessage(Message $message): array
    {
        $message->loadMissing('attachments');
        $attachments = $message->attachments
            ->map(fn ($a) => $this->mediaService->serializeAttachment($a))
            ->values()
            ->all();

        $primary = $attachments[0] ?? null;

        return [
            'id' => $message->id,
            'from' => match ($message->sender_type) {
                MessageSenderType::Client => 'client',
                MessageSenderType::Bot => 'bot',
                default => 'agent',
            },
            'type' => $message->type?->value ?? 'text',
            'text' => $message->content,
            'time' => $message->created_at->format('H:i'),
            'status' => $message->status->value,
            'attachments' => $attachments,
            'image_url' => ($primary && ($primary['is_image'] ?? false)) ? $primary['url'] : null,
        ];
    }

    private function touchConversationAfterAgentSend(Conversation $conversation, int $agentId): void
    {
        $conversation->update([
            'last_message_at' => now(),
            'status' => ConversationStatus::InProgress,
            'assigned_to' => $conversation->assigned_to ?? $agentId,
            'first_response_at' => $conversation->first_response_at ?? now(),
            'is_bot_handled' => false,
        ]);
        $this->sla->clear($conversation);

        $conversation->client->update(['last_contact_at' => now()]);
    }

    private function lockForAgentSend(Conversation $conversation, int $agentId): Conversation
    {
        $locked = $this->workflow->lock($conversation);

        if ($locked->status->isReadOnlyForAgents()) {
            throw new \RuntimeException('Esta conversa está sob atendimento do bot e não permite envio de mensagens.');
        }
        if ($locked->assigned_to !== null && (int) $locked->assigned_to !== $agentId) {
            throw new \RuntimeException('Esta conversa já foi assumida por outro atendente.');
        }

        return $locked;
    }

    private function resolveQuickReply(string $content): string
    {
        if (! str_starts_with(trim($content), '/')) {
            return $content;
        }

        $shortcut = trim($content);
        $reply = \Infrastructure\Persistence\Eloquent\Models\QuickReply::query()
            ->where('shortcut', $shortcut)
            ->first();

        if ($reply) {
            $reply->increment('usage_count');

            return $reply->content;
        }

        return $content;
    }

    private function resolveMessageType(string $type): MessageType
    {
        return match ($type) {
            'image', 'sticker' => MessageType::Image,
            'document' => MessageType::Document,
            'audio' => MessageType::Audio,
            'video' => MessageType::Video,
            default => MessageType::Text,
        };
    }
}
