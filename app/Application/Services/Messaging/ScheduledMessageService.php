<?php

namespace Application\Services\Messaging;

use App\Jobs\SendWhatsAppMessageJob;
use Domain\Shared\Enums\MessageSenderType;
use Domain\Shared\Enums\MessageStatus;
use Domain\Shared\Enums\MessageType;
use Domain\Shared\Enums\ScheduledMessageStatus;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\ScheduledMessage;
use Illuminate\Support\Facades\DB;

class ScheduledMessageService
{
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
                'status' => ScheduledMessageStatus::Sent,
                'sent_at' => now(),
                'error_message' => null,
            ]);

            SendWhatsAppMessageJob::dispatch($message->id);

            return true;
        });
    }
}
