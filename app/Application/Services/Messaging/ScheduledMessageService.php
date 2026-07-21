<?php

namespace Application\Services\Messaging;

use App\Jobs\SendWhatsAppMessageJob;
use Domain\Shared\Enums\MessageSenderType;
use Domain\Shared\Enums\MessageStatus;
use Domain\Shared\Enums\MessageType;
use Domain\Shared\Enums\ScheduledMessageStatus;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\ScheduledMessage;
use Illuminate\Support\Facades\DB;

class ScheduledMessageService
{
    public function listForConversation(Conversation $conversation): array
    {
        return ScheduledMessage::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('status', [
                ScheduledMessageStatus::Pending,
                ScheduledMessageStatus::Processing,
                ScheduledMessageStatus::Failed,
            ])
            ->orderBy('scheduled_at')
            ->limit(50)
            ->get()
            ->map(fn (ScheduledMessage $item) => $this->serialize($item))
            ->values()
            ->all();
    }

    public function listRecent(int $limit = 100): array
    {
        return ScheduledMessage::query()
            ->with(['client', 'conversation', 'creator'])
            ->latest('scheduled_at')
            ->limit($limit)
            ->get()
            ->map(fn (ScheduledMessage $item) => $this->serialize($item, true))
            ->values()
            ->all();
    }

    public function scheduleForConversation(
        Conversation $conversation,
        int $createdBy,
        string $content,
        string|\DateTimeInterface $scheduledAt,
    ): ScheduledMessage {
        if ($conversation->client_id === null) {
            throw new \RuntimeException('Conversa sem cliente.');
        }

        return ScheduledMessage::query()->create([
            'client_id' => $conversation->client_id,
            'conversation_id' => $conversation->id,
            'created_by' => $createdBy,
            'channel' => 'whatsapp',
            'type' => 'text',
            'content' => $content,
            'scheduled_at' => $scheduledAt,
            'status' => ScheduledMessageStatus::Pending,
        ]);
    }

    public function cancel(ScheduledMessage $item): void
    {
        if ($item->status !== ScheduledMessageStatus::Pending) {
            throw new \RuntimeException('Só é possível cancelar mensagens ainda pendentes.');
        }

        $item->update(['status' => ScheduledMessageStatus::Cancelled]);
    }

    public function serialize(ScheduledMessage $item, bool $withRelations = false): array
    {
        $data = [
            'id' => $item->id,
            'content' => $item->content,
            'status' => $item->status?->value ?? (string) $item->status,
            'status_label' => $item->status?->label() ?? (string) $item->status,
            'scheduled_at' => $item->scheduled_at?->toIso8601String(),
            'scheduled_at_label' => $item->scheduled_at?->format('d/m/Y H:i'),
            'sent_at' => $item->sent_at?->format('d/m/Y H:i'),
            'error_message' => $item->error_message,
            'conversation_id' => $item->conversation_id,
            'client_id' => $item->client_id,
            'can_cancel' => $item->status === ScheduledMessageStatus::Pending,
        ];

        if ($withRelations) {
            $data['client_name'] = $item->client?->name;
            $data['creator_name'] = $item->creator?->name;
        }

        return $data;
    }

    public function dispatchDue(int $limit = 50): int
    {
        $due = ScheduledMessage::query()
            ->due()
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        $sent = 0;

        foreach ($due as $item) {
            if ($this->dispatchOne($item)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function dispatchOne(ScheduledMessage $item): bool
    {
        return DB::transaction(function () use ($item) {
            $locked = ScheduledMessage::query()
                ->whereKey($item->id)
                ->where('status', ScheduledMessageStatus::Pending)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return false;
            }

            $locked->update([
                'status' => ScheduledMessageStatus::Processing,
                'attempts' => $locked->attempts + 1,
            ]);

            $conversationId = $locked->conversation_id;
            if (! $conversationId) {
                $conversation = $locked->client?->conversations()->latest('id')->first();
                $conversationId = $conversation?->id;
            }

            if (! $conversationId) {
                $locked->update([
                    'status' => ScheduledMessageStatus::Failed,
                    'error_message' => 'Cliente sem conversa para envio.',
                ]);

                return false;
            }

            $message = Message::query()->create([
                'conversation_id' => $conversationId,
                'sender_type' => MessageSenderType::Agent,
                'sender_id' => $locked->created_by,
                'type' => MessageType::Text,
                'content' => $locked->content,
                'status' => MessageStatus::Pending,
                'metadata' => [
                    'scheduled_message_id' => $locked->id,
                    'channel' => $locked->channel,
                ],
            ]);

            $locked->update([
                'conversation_id' => $conversationId,
                'message_id' => $message->id,
                'error_message' => null,
            ]);

            SendWhatsAppMessageJob::dispatch($message->id)->afterCommit();

            return true;
        });
    }
}
