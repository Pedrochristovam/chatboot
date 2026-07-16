<?php

namespace Infrastructure\Persistence\Eloquent\Models;

use Domain\Shared\Enums\ConversationOrigin;
use Domain\Shared\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'assigned_to',
        'closed_by',
        'department_id',
        'queue_id',
        'status',
        'origin',
        'subject',
        'priority',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'bot_closed_at',
        'transferred_to_human_at',
        'last_message_at',
        'sla_due_at',
        'waiting_since',
        'unread_count',
        'is_bot_handled',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConversationStatus::class,
            'origin' => ConversationOrigin::class,
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'bot_closed_at' => 'datetime',
            'transferred_to_human_at' => 'datetime',
            'last_message_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'waiting_since' => 'datetime',
            'is_bot_handled' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function closedByAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'conversation_tags');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(ConversationTransfer::class);
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(ConversationInternalNote::class);
    }

    public function scheduledMessages(): HasMany
    {
        return $this->hasMany(ScheduledMessage::class);
    }
}
