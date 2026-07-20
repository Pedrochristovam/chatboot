<?php

namespace Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookReceipt extends Model
{
    protected $fillable = [
        'provider',
        'idempotency_key',
        'event_type',
        'external_message_id',
        'conversation_key',
        'event_status',
        'payload',
        'signature_valid',
        'processing_status',
        'attempts',
        'dispatched_at',
        'last_attempt_at',
        'processed_at',
        'failed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'signature_valid' => 'boolean',
            'dispatched_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'processed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
