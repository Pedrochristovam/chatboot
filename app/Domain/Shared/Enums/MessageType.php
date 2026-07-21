<?php

namespace Domain\Shared\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case Audio = 'audio';
    case Document = 'document';
    case Video = 'video';
    case Location = 'location';
    case Sticker = 'sticker';
    case Template = 'template';
}
