<?php

namespace Application\DTOs\WhatsApp;

readonly class OutgoingMessageDTO
{
    public function __construct(
        public string $to,
        public string $content,
        public ?string $mediaUrl = null,
        public ?string $mediaType = null,
        public ?string $fileName = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public array $metadata = [],
    ) {}
}
