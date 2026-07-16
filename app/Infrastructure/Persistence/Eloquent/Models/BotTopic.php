<?php

namespace Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotTopic extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'description',
        'sort_order',
        'is_active',
        'transfers_to_human',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'transfers_to_human' => 'boolean',
        ];
    }

    public function knowledge(): HasMany
    {
        return $this->hasMany(BotKnowledge::class);
    }

    public function interactiveId(): string
    {
        return 'topic_'.$this->id;
    }
}
