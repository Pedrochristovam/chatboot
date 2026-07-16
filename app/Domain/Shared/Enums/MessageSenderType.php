<?php

namespace Domain\Shared\Enums;

enum MessageSenderType: string
{
    case Client = 'client';
    case Agent = 'agent';
    case System = 'system';
    case Bot = 'bot';
}
