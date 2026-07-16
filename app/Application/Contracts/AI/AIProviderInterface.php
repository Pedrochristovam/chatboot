<?php

namespace Application\Contracts\AI;

use Application\DTOs\AI\AIReplyDTO;
use Application\DTOs\AI\ClassificationDTO;
use Application\DTOs\AI\CustomerInfoDTO;
use Application\DTOs\AI\SummaryDTO;

interface AIProviderInterface
{
    public function generateReply(string $conversationContext): AIReplyDTO;

    public function summarizeConversation(string $conversationContext): SummaryDTO;

    public function classifyConversation(string $conversationContext): ClassificationDTO;

    public function suggestTags(string $conversationContext): array;

    public function extractCustomerInformation(string $conversationContext): CustomerInfoDTO;
}
