<?php

namespace Domain\Shared\Enums;

enum ConversationOrigin: string
{
    case Whatsapp = 'whatsapp';
    case Manual = 'manual';
    case Api = 'api';
}
