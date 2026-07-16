<?php

namespace Application\DTOs\AI;

readonly class SummaryDTO
{
    public function __construct(
        public string $summary,
        public array $keyPoints = [],
    ) {}
}
