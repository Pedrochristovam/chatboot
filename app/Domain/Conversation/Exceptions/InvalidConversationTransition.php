<?php

namespace Domain\Conversation\Exceptions;

use Domain\Shared\Enums\ConversationStatus;
use RuntimeException;

class InvalidConversationTransition extends RuntimeException
{
    public static function fromStatuses(ConversationStatus $from, ConversationStatus $to): self
    {
        return new self("Transição de conversa não permitida: {$from->value} -> {$to->value}.");
    }
}
