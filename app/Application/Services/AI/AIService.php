<?php

namespace Application\Services\AI;

use Application\Contracts\AI\AIProviderInterface;
use Application\DTOs\AI\AIReplyDTO;
use Application\DTOs\AI\ClassificationDTO;
use Application\DTOs\AI\CustomerInfoDTO;
use Application\DTOs\AI\SummaryDTO;

class AIService
{
    public function __construct(
        private readonly AIProviderInterface $provider,
    ) {}

    public function generateReply(string $conversationContext): AIReplyDTO
    {
        return $this->provider->generateReply($conversationContext);
    }

    public function summarizeConversation(string $conversationContext): SummaryDTO
    {
        return $this->provider->summarizeConversation($conversationContext);
    }

    public function classifyConversation(string $conversationContext): ClassificationDTO
    {
        return $this->provider->classifyConversation($conversationContext);
    }

    public function suggestTags(string $conversationContext): array
    {
        return $this->provider->suggestTags($conversationContext);
    }

    public function extractCustomerInformation(string $conversationContext): CustomerInfoDTO
    {
        return $this->provider->extractCustomerInformation($conversationContext);
    }
}
