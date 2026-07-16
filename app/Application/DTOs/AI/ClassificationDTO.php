<?php

namespace Application\DTOs\AI;

readonly class ClassificationDTO
{
    public function __construct(
        public string $category,
        public float $confidence = 0.0,
        public array $suggestedTags = [],
    ) {}
}
