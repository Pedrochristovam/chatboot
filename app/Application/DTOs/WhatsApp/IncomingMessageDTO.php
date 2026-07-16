<?php

namespace Application\DTOs\WhatsApp;

readonly class IncomingMessageDTO
{
    public function __construct(
        public string $from,
        public string $messageId,
        public string $type,
        public ?string $content = null,
        public ?string $mediaUrl = null,
        public ?string $mediaMimeType = null,
        public ?string $fileName = null,
        public ?string $mediaId = null,
        public array $metadata = [],
    ) {}
}
