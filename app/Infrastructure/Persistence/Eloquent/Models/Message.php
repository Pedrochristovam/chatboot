<?php

namespace Infrastructure\Persistence\Eloquent\Models;

use Domain\Shared\Enums\MessageSenderType;
use Domain\Shared\Enums\MessageStatus;
use Domain\Shared\Enums\MessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'type',
        'content',
        'whatsapp_message_id',
        'status',
        'error_message',
        'metadata',
        'read_at',
        'sent_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'sender_type' => MessageSenderType::class,
            'type' => MessageType::class,
            'status' => MessageStatus::class,
            'metadata' => 'array',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(MessageStatusEvent::class);
    }
}
