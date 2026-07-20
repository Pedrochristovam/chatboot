<?php

namespace Application\Services\Conversation;

use App\Events\MessageStatusUpdated;
use Application\Services\Settings\FeatureFlagService;
use Domain\Shared\Enums\MessageStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Infrastructure\Logging\AuditLogger;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\MessageStatusEvent;
use Infrastructure\Persistence\Eloquent\Models\WebhookReceipt;

class MessageStatusService
{
    public function __construct(
        private readonly FeatureFlagService $features,
        private readonly AuditLogger $audit,
    ) {}

    public function applyWebhookStatus(
        string $whatsappMessageId,
        string $status,
        ?string $providerEventId = null,
        array $payload = [],
        ?string $occurredAt = null,
    ): ?Message {
        if (! $this->features->isEnabled('message_status_webhooks', true)) {
            return null;
        }

        $mapped = $this->mapStatus($status);
        if (! $mapped) {
            return null;
        }

        $at = $occurredAt ? Carbon::parse($occurredAt) : now();

        return DB::transaction(function () use (
            $whatsappMessageId,
            $mapped,
            $providerEventId,
            $payload,
            $at,
        ): ?Message {
            $message = Message::query()
                ->where('whatsapp_message_id', $whatsappMessageId)
                ->lockForUpdate()
                ->first();

            if (! $message) {
                return null;
            }

            if ($providerEventId) {
                $event = MessageStatusEvent::query()->firstOrCreate(
                    ['provider_event_id' => $providerEventId],
                    [
                        'message_id' => $message->id,
                        'status' => $mapped->value,
                        'payload' => $payload,
                        'occurred_at' => $at,
                        'created_at' => now(),
                    ],
                );

                if (! $event->wasRecentlyCreated) {
                    return $message->fresh();
                }
            } else {
                MessageStatusEvent::query()->create([
                    'message_id' => $message->id,
                    'status' => $mapped->value,
                    'provider_event_id' => null,
                    'payload' => $payload,
                    'occurred_at' => $at,
                    'created_at' => now(),
                ]);
            }

            $current = $message->status instanceof MessageStatus
                ? $message->status
                : MessageStatus::tryFrom((string) $message->status) ?? MessageStatus::Pending;
            $updates = [];

            if ($this->canTransition($current, $mapped)) {
                $updates['status'] = $mapped;
            }
            if ($mapped === MessageStatus::Sent && ! $message->sent_at) {
                $updates['sent_at'] = $at;
            }
            if ($mapped === MessageStatus::Delivered && ! $message->delivered_at) {
                $updates['delivered_at'] = $at;
            }
            if ($mapped === MessageStatus::Read) {
                $updates['read_at'] = $message->read_at
                    ? min($message->read_at, $at)
                    : $at;
                if (! $message->delivered_at) {
                    $updates['delivered_at'] = $at;
                }
            }
            if ($mapped === MessageStatus::Failed
                && in_array($current, [MessageStatus::Pending, MessageStatus::Sent, MessageStatus::Failed], true)) {
                $updates['error_message'] = $payload['errors'][0]['title']
                    ?? $payload['errors'][0]['message']
                    ?? 'Falha reportada pelo WhatsApp';
            }

            if ($updates !== []) {
                $message->update($updates);
                DB::afterCommit(fn () => event(new MessageStatusUpdated($message->fresh())));
            }

            if ($this->features->isEnabled('audit_log', true)) {
                $this->audit->log('message.status_updated', $message, null, [
                    'status' => $mapped->value,
                    'effective_status' => $message->fresh()->status->value,
                    'whatsapp_message_id' => $whatsappMessageId,
                ]);
            }

            return $message->fresh();
        });
    }

    public function reconcilePendingForMessage(Message $message): void
    {
        if (! filled($message->whatsapp_message_id)) {
            return;
        }

        WebhookReceipt::query()
            ->where('provider', 'meta')
            ->where('event_type', 'status')
            ->where('external_message_id', $message->whatsapp_message_id)
            ->whereIn('processing_status', ['received', 'retrying', 'retained', 'processing'])
            ->orderBy('id')
            ->get()
            ->each(function (WebhookReceipt $receipt): void {
                $resolved = $this->applyWebhookStatus(
                    whatsappMessageId: (string) $receipt->external_message_id,
                    status: (string) $receipt->event_status,
                    providerEventId: substr($receipt->idempotency_key, strlen('status:')),
                    payload: $receipt->payload,
                    occurredAt: isset($receipt->payload['timestamp'])
                        ? Carbon::createFromTimestamp((int) $receipt->payload['timestamp'])->toIso8601String()
                        : null,
                );

                if ($resolved) {
                    $receipt->update([
                        'processing_status' => 'processed',
                        'processed_at' => now(),
                        'last_error' => null,
                    ]);
                }
            });
    }

    private function mapStatus(string $status): ?MessageStatus
    {
        return match (strtolower($status)) {
            'sent' => MessageStatus::Sent,
            'delivered' => MessageStatus::Delivered,
            'read' => MessageStatus::Read,
            'failed' => MessageStatus::Failed,
            default => null,
        };
    }

    private function statusRank(MessageStatus $status): int
    {
        return match ($status) {
            MessageStatus::Pending => 0,
            MessageStatus::Sent => 10,
            MessageStatus::Delivered => 20,
            MessageStatus::Read => 30,
            MessageStatus::Failed => 40,
        };
    }

    private function canTransition(MessageStatus $current, MessageStatus $next): bool
    {
        if ($current === $next) {
            return true;
        }

        if ($current === MessageStatus::Failed) {
            return false;
        }

        if ($next === MessageStatus::Failed) {
            return in_array($current, [MessageStatus::Pending, MessageStatus::Sent], true);
        }

        return $this->statusRank($next) > $this->statusRank($current);
    }
}
