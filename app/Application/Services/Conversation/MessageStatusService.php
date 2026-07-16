<?php

namespace Application\Services\Conversation;

use Application\Services\Settings\FeatureFlagService;
use Domain\Shared\Enums\MessageStatus;
use Infrastructure\Logging\AuditLogger;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\MessageStatusEvent;

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

        $message = Message::query()
            ->where('whatsapp_message_id', $whatsappMessageId)
            ->first();

        if (! $message) {
            return null;
        }

        $mapped = $this->mapStatus($status);
        if (! $mapped) {
            return null;
        }

        if ($providerEventId) {
            $exists = MessageStatusEvent::query()
                ->where('provider_event_id', $providerEventId)
                ->exists();
            if ($exists) {
                return $message;
            }
        }

        $at = $occurredAt ? \Illuminate\Support\Carbon::parse($occurredAt) : now();

        MessageStatusEvent::query()->create([
            'message_id' => $message->id,
            'status' => $mapped->value,
            'provider_event_id' => $providerEventId,
            'payload' => $payload,
            'occurred_at' => $at,
            'created_at' => now(),
        ]);

        $updates = ['status' => $mapped];
        if ($mapped === MessageStatus::Sent && ! $message->sent_at) {
            $updates['sent_at'] = $at;
        }
        if ($mapped === MessageStatus::Delivered) {
            $updates['delivered_at'] = $at;
        }
        if ($mapped === MessageStatus::Read) {
            $updates['read_at'] = $at;
            if (! $message->delivered_at) {
                $updates['delivered_at'] = $at;
            }
        }
        if ($mapped === MessageStatus::Failed) {
            $updates['error_message'] = $payload['errors'][0]['title']
                ?? $payload['errors'][0]['message']
                ?? 'Falha reportada pelo WhatsApp';
        }

        $message->update($updates);

        if ($this->features->isEnabled('audit_log', true)) {
            $this->audit->log('message.status_updated', $message, null, [
                'status' => $mapped->value,
                'whatsapp_message_id' => $whatsappMessageId,
            ]);
        }

        return $message->fresh();
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
}
