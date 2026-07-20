<?php

namespace Domain\Conversation\Events;

use Domain\Shared\Enums\ConversationStatus;

final readonly class ConversationStatusChanged
{
    public function __construct(
        public int $conversationId,
        public ConversationStatus $from,
        public ConversationStatus $to,
    ) {}
}
