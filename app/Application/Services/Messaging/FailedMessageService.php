<?php

namespace Application\Services\Messaging;

use App\Jobs\SendWhatsAppMessageJob;
use Domain\Shared\Enums\MessageSenderType;
use Domain\Shared\Enums\MessageStatus;
use Domain\Shared\Enums\ScheduledMessageStatus;
use Illuminate\Support\Facades\DB;
use Infrastructure\Logging\AuditLogger;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\ScheduledMessage;

class FailedMessageService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function retry(Message $message): Message
    {
        $message = DB::transaction(function () use ($message) {
            $locked = Message::query()->lockForUpdate()->findOrFail($message->id);

            if ($locked->status !== MessageStatus::Failed) {
                throw new \RuntimeException('A mensagem não está com falha.');
            }
            if ($locked->sender_type === MessageSenderType::Client) {
                throw new \RuntimeException('Mensagens recebidas não podem ser reenviadas.');
            }
            if (($locked->metadata['bot_delivery_guard']['cancelled'] ?? false) === true) {
                throw new \RuntimeException('Esta resposta do bot foi cancelada por mudança de responsável e não pode ser reenviada.');
            }
            if (filled($locked->whatsapp_message_id)) {
                throw new \RuntimeException('A Meta já confirmou esta mensagem; o reenvio foi bloqueado para evitar duplicidade.');
            }

            $metadata = $locked->metadata ?? [];
            $metadata['manual_retry_count'] = ((int) ($metadata['manual_retry_count'] ?? 0)) + 1;
            $metadata['last_manual_retry_at'] = now()->toIso8601String();

            $locked->update([
                'status' => MessageStatus::Pending,
                'error_message' => null,
                'metadata' => $metadata,
            ]);

            if ($scheduledId = ($metadata['scheduled_message_id'] ?? null)) {
                ScheduledMessage::query()->whereKey($scheduledId)->update([
                    'status' => ScheduledMessageStatus::Processing->value,
                    'error_message' => null,
                ]);
            }

            SendWhatsAppMessageJob::dispatch($locked->id)->afterCommit();

            return $locked;
        });

        $this->audit->log('message.manual_retry', $message, ['status' => 'failed'], ['status' => 'pending']);

        return $message->fresh();
    }
}
