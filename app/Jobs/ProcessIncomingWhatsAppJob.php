<?php

namespace App\Jobs;

use App\Events\MessageReceived;
use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\Services\Conversation\MessageService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\WebhookReceipt;
use Throwable;

class ProcessIncomingWhatsAppJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 20;

    public int $timeout = 120;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 3600;

    public function __construct(
        public string $from,
        public string $messageId,
        public string $type,
        public ?string $content,
        public array $metadata = [],
        public ?string $mediaUrl = null,
        public ?string $mediaMimeType = null,
        public ?string $fileName = null,
        public ?string $mediaId = null,
        public ?int $receiptId = null,
    ) {}

    public static function fromDto(IncomingMessageDTO $dto): self
    {
        return new self(
            from: $dto->from,
            messageId: $dto->messageId,
            type: $dto->type,
            content: $dto->content,
            metadata: $dto->metadata,
            mediaUrl: $dto->mediaUrl,
            mediaMimeType: $dto->mediaMimeType,
            fileName: $dto->fileName,
            mediaId: $dto->mediaId,
        );
    }

    public function uniqueId(): string
    {
        return $this->receiptId ? 'receipt:'.$this->receiptId : 'message:'.$this->messageId;
    }

    public function backoff(): array
    {
        return [2, 5, 15, 30, 60, 120, 300];
    }

    public function middleware(): array
    {
        $key = preg_replace('/\D+/', '', $this->from) ?: $this->from;

        return [
            (new WithoutOverlapping('whatsapp-conversation:'.$key))
                ->shared()
                ->releaseAfter(2)
                ->expireAfter($this->timeout + 30),
        ];
    }

    public function handle(MessageService $messageService): void
    {
        $receipt = $this->receiptId ? WebhookReceipt::query()->find($this->receiptId) : null;
        if ($receipt?->processing_status === 'processed') {
            return;
        }

        if ($receipt && WebhookReceipt::query()
            ->where('event_type', 'message')
            ->where('conversation_key', $receipt->conversation_key)
            ->where('id', '<', $receipt->id)
            ->whereNotNull('dispatched_at')
            ->whereIn('processing_status', ['received', 'retrying', 'processing'])
            ->exists()) {
            $this->release(2);

            return;
        }

        $receipt?->update([
            'processing_status' => 'processing',
            'attempts' => max($receipt->attempts + 1, $this->attempts()),
            'last_attempt_at' => now(),
            'last_error' => null,
        ]);

        $dto = new IncomingMessageDTO(
            from: $this->from,
            messageId: $this->messageId,
            type: $this->type,
            content: $this->content,
            mediaUrl: $this->mediaUrl,
            mediaMimeType: $this->mediaMimeType,
            fileName: $this->fileName,
            mediaId: $this->mediaId,
            metadata: $this->metadata,
        );

        try {
            $existing = Message::query()
                ->where('whatsapp_message_id', $this->messageId)
                ->first();
            $message = $existing ?: $messageService->processIncoming($dto);

            if (! $existing) {
                event(new MessageReceived($message));
            }

            $receipt?->update([
                'processing_status' => 'processed',
                'processed_at' => now(),
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
