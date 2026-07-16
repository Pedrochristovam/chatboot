<?php

namespace App\Jobs;

use App\Events\MessageReceived;
use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\Services\Conversation\MessageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIncomingWhatsAppJob implements ShouldQueue
{
    use Queueable;

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

    public function handle(MessageService $messageService): void
    {
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

        $message = $messageService->processIncoming($dto);

        event(new MessageReceived($message));
    }
}
