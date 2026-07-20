<?php

namespace App\Jobs;

use Application\Contracts\WhatsApp\WhatsAppProviderInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Infrastructure\Persistence\Eloquent\Models\WebhookReceipt;
use Infrastructure\WhatsApp\MetaCloudProvider;
use Throwable;

class ProcessMetaWebhookJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $timeout = 90;

    public int $uniqueFor = 3600;

    public function __construct(public int $receiptId) {}

    public function uniqueId(): string
    {
        return 'meta-webhook:'.$this->receiptId;
    }

    public function backoff(): array
    {
        return [2, 5, 15, 30, 60];
    }

    public function handle(WhatsAppProviderInterface $provider): void
    {
        $receipt = WebhookReceipt::query()->find($this->receiptId);
        if (! $receipt || $receipt->processing_status === 'processed') {
            return;
        }

        $receipt->update([
            'processing_status' => 'processing',
            'attempts' => max($receipt->attempts + 1, $this->attempts()),
            'last_attempt_at' => now(),
            'last_error' => null,
        ]);

        $payload = $receipt->payload ?? [];
        $signatureValid = $receipt->signature_valid;
        $parsed = isset($payload['entry'])
            ? app(MetaCloudProvider::class)->parseWebhook($payload)
            : ['messages' => [$provider->receiveWebhook($payload)], 'statuses' => []];

        $queued = 0;

        foreach ($parsed['messages'] as $dto) {
            if (empty($dto->from) || empty($dto->messageId)) {
                continue;
            }

            $child = WebhookReceipt::query()->firstOrCreate(
                [
                    'provider' => 'meta',
                    'idempotency_key' => 'message:'.$dto->messageId,
                ],
                [
                    'event_type' => 'message',
                    'external_message_id' => $dto->messageId,
                    'conversation_key' => preg_replace('/\D+/', '', $dto->from) ?: $dto->from,
                    'payload' => [
                        'from' => $dto->from,
                        'message_id' => $dto->messageId,
                        'type' => $dto->type,
                        'content' => $dto->content,
                        'metadata' => $dto->metadata,
                        'media_url' => $dto->mediaUrl,
                        'media_mime_type' => $dto->mediaMimeType,
                        'file_name' => $dto->fileName,
                        'media_id' => $dto->mediaId,
                    ],
                    'signature_valid' => $signatureValid,
                ],
            );

            if (! in_array($child->processing_status, ['processed', 'failed'], true)) {
                ProcessIncomingWhatsAppJob::dispatch(
                    from: $dto->from,
                    messageId: $dto->messageId,
                    type: $dto->type,
                    content: $dto->content,
                    metadata: $dto->metadata,
                    mediaUrl: $dto->mediaUrl,
                    mediaMimeType: $dto->mediaMimeType,
                    fileName: $dto->fileName,
                    mediaId: $dto->mediaId,
                    receiptId: $child->id,
                );
                $child->update(['dispatched_at' => now()]);
                $queued++;
            }
        }

        foreach ($parsed['statuses'] as $statusRow) {
            $waId = $statusRow['id'] ?? null;
            $status = $statusRow['status'] ?? null;
            if (! is_string($waId) || $waId === '' || ! is_string($status) || $status === '') {
                continue;
            }

            $providerEventId = implode(':', [
                $waId,
                strtolower($status),
                (string) ($statusRow['timestamp'] ?? hash('sha256', json_encode($statusRow))),
            ]);

            $child = WebhookReceipt::query()->firstOrCreate(
                [
                    'provider' => 'meta',
                    'idempotency_key' => 'status:'.$providerEventId,
                ],
                [
                    'event_type' => 'status',
                    'external_message_id' => $waId,
                    'event_status' => strtolower($status),
                    'payload' => $statusRow,
                    'signature_valid' => $signatureValid,
                ],
            );

            if (! in_array($child->processing_status, ['processed', 'failed'], true)) {
                ProcessWhatsAppStatusJob::dispatch(
                    whatsappMessageId: $waId,
                    status: $status,
                    providerEventId: $providerEventId,
                    payload: $statusRow,
                    occurredAt: isset($statusRow['timestamp'])
                        ? now()->setTimestamp((int) $statusRow['timestamp'])->toIso8601String()
                        : null,
                    receiptId: $child->id,
                );
                $child->update(['dispatched_at' => now()]);
                $queued++;
            }
        }

        $receipt->update([
            'processing_status' => 'processed',
            'processed_at' => now(),
            'last_error' => $queued === 0 ? 'no_dispatchable_events' : null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        WebhookReceipt::query()->whereKey($this->receiptId)->update([
            'processing_status' => 'failed',
            'failed_at' => now(),
            'last_error' => mb_substr($exception?->getMessage() ?? 'Job failed.', 0, 65535),
        ]);
    }
}
