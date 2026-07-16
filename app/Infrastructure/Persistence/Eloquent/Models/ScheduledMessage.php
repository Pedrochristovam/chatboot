<?php

namespace Infrastructure\Persistence\Eloquent\Models;

use Domain\Shared\Enums\ScheduledMessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduledMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'conversation_id',
        'created_by',
        'channel',
        'type',
        'content',
        'payload',
        'scheduled_at',
        'sent_at',
        'status',
        'attempts',
        'error_message',
        'message_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'status' => ScheduledMessageStatus::class,
            'attempts' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function scopeDue($query)
    {
        return $query
            ->where('status', ScheduledMessageStatus::Pending)
            ->where('scheduled_at', '<=', now());
    }
}
