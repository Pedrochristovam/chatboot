<?php

namespace Domain\Shared\Enums;

enum ConversationStatus: string
{
    case BotActive = 'bot_active';
    case BotClosed = 'bot_closed';
    case Waiting = 'waiting';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::BotActive => 'Bot ativo',
            self::BotClosed => 'Encerrada pelo bot',
            self::Waiting => 'Aguardando',
            self::InProgress => 'Em atendimento',
            self::Resolved => 'Resolvida',
            self::Closed => 'Encerrada por atendente',
        };
    }

    public function isBot(): bool
    {
        return in_array($this, [self::BotActive, self::BotClosed], true);
    }

    public function isReadOnlyForAgents(): bool
    {
        return in_array($this, [self::BotActive, self::BotClosed, self::Closed, self::Resolved], true);
    }
}
