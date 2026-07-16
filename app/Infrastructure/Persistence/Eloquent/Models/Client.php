<?php

namespace Infrastructure\Persistence\Eloquent\Models;

use Domain\Shared\Enums\ClientStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'whatsapp_name',
        'phone',
        'phone_normalized',
        'email',
        'document',
        'company',
        'notes',
        'status',
        'source',
        'last_contact_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClientStatus::class,
            'last_contact_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Client $client) {
            if ($client->phone) {
                $client->phone_normalized = preg_replace('/\D+/', '', $client->phone) ?: null;
            }
        });
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function scheduledMessages(): HasMany
    {
        return $this->hasMany(ScheduledMessage::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'client_tags');
    }
}
