<?php

namespace Application\DTOs\AI;

readonly class AIReplyDTO
{
    public function __construct(
        public string $reply,
        public float $confidence = 0.0,
    ) {}
}
