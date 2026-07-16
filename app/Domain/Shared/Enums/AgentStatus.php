<?php

namespace Domain\Shared\Enums;

enum AgentStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Away = 'away';
    case Busy = 'busy';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Offline => 'Offline',
            self::Away => 'Ausente',
            self::Busy => 'Ocupado',
        };
    }
}
