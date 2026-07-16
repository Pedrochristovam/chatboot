<?php

namespace Application\DTOs\WhatsApp;

readonly class SendResultDTO
{
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public ?string $error = null,
    ) {}
}
