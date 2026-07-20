<?php

namespace App\Jobs;

use Application\Services\Conversation\MessageStatusService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Infrastructure\Persistence\Eloquent\Models\WebhookReceipt;
use Throwable;

class ProcessWhatsAppStatusJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 8;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 3600;

    public function __construct(
        public string $whatsappMessageId,
        public string $status,
        public ?string $providerEventId = null,
        public array $payload = [],
        public ?string $occurredAt = null,
        public ?int $receiptId = null,
    ) {}

    public function uniqueId(): string
    {
        return $this->receiptId
            ? 'receipt:'.$this->receiptId
            : 'status:'.($this->providerEventId ?: $this->whatsappMessageId.':'.$this->status);
    }

    public function backoff(): array
    {
        return [2, 5, 15, 30, 60, 120, 300];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('whatsapp-status:'.$this->whatsappMessageId))
                ->releaseAfter(2)
                ->expireAfter($this->timeout + 30),
        ];
    }

    public function handle(MessageStatusService $statusService): void
    {
        $receipt = $this->receiptId ? WebhookReceipt::query()->find($this->receiptId) : null;
        if ($receipt?->processing_status === 'processed') {
            return;
        }

        $receipt?->update([
            'processing_status' => 'processing',
            'attempts' => max($receipt->attempts + 1, $this->attempts()),
            'last_attempt_at' => now(),
            'last_error' => null,
        ]);

        try {
            $message = $statusService->applyWebhookStatus(
                whatsappMessageId: $this->whatsappMessageId,
                status: $this->status,
                providerEventId: $this->providerEventId,
                payload: $this->payload,
                occurredAt: $this->occurredAt,
            );

            $receipt?->update([
                'processing_status' => $message ? 'processed' : 'retained',
                'processed_at' => $message ? now() : null,
                'last_error' => null,
            ]);
        } catch (Throwable $exception) {
            $receipt?->update([
                'processing_status' => 'retrying',
                'last_error' => mb_substr($exception->getMessage(), 0, 65535),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        if (! $this->receiptId) {
            return;
        }

        WebhookReceipt::query()->whereKey($this->receiptId)->update([
            'processing_status' => 'failed',
            'failed_at' => now(),
            'last_error' => mb_substr($exception?->getMessage() ?? 'Job failed.', 0, 65535),
        ]);
    }
}
