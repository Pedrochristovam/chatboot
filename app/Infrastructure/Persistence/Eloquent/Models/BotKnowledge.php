<?php

namespace Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotKnowledge extends Model
{
    protected $table = 'bot_knowledge';

    protected $fillable = [
        'bot_topic_id',
        'question',
        'answer',
        'keywords',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(BotTopic::class, 'bot_topic_id');
    }
}
