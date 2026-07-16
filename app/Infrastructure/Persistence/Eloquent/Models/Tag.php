<?php

namespace Infrastructure\Persistence\Eloquent\Models;

use Domain\Shared\Enums\TagType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => TagType::class,
            'is_active' => 'boolean',
        ];
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_tags');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_tags');
    }
}
