<?php

namespace Infrastructure\AI;

use Application\Contracts\AI\AIProviderInterface;
use Application\DTOs\AI\AIReplyDTO;
use Application\DTOs\AI\ClassificationDTO;
use Application\DTOs\AI\CustomerInfoDTO;
use Application\DTOs\AI\SummaryDTO;

class NullAIProvider implements AIProviderInterface
{
    public function generateReply(string $conversationContext): AIReplyDTO
    {
        return new AIReplyDTO(reply: '', confidence: 0.0);
    }

    public function summarizeConversation(string $conversationContext): SummaryDTO
    {
        return new SummaryDTO(summary: '', keyPoints: []);
    }

    public function classifyConversation(string $conversationContext): ClassificationDTO
    {
        return new ClassificationDTO(category: 'general', confidence: 0.0);
    }

    public function suggestTags(string $conversationContext): array
    {
        return [];
    }

    public function extractCustomerInformation(string $conversationContext): CustomerInfoDTO
    {
        return new CustomerInfoDTO(extracted: []);
    }
}
