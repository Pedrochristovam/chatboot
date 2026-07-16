<?php

namespace App\Jobs;

use Application\Services\Conversation\MessageStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessWhatsAppStatusJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $whatsappMessageId,
        public string $status,
        public ?string $providerEventId = null,
        public array $payload = [],
        public ?string $occurredAt = null,
    ) {}

    public function handle(MessageStatusService $statusService): void
    {
        $statusService->applyWebhookStatus(
            whatsappMessageId: $this->whatsappMessageId,
            status: $this->status,
            providerEventId: $this->providerEventId,
            payload: $this->payload,
            occurredAt: $this->occurredAt,
        );
    }
}
