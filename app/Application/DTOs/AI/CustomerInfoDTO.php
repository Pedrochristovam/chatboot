<?php

namespace Application\DTOs\AI;

readonly class CustomerInfoDTO
{
    public function __construct(
        public array $extracted = [],
    ) {}
}
