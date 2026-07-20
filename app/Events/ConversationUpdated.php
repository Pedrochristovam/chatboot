<?php

namespace App\Events;

use Application\Services\Conversation\ConversationService;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Infrastructure\Persistence\Eloquent\Models\Conversation;

class ConversationUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $conversationId,
        public string $reason,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('inbox')];
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    public function broadcastWith(): array
    {
        $conversation = Conversation::query()
            ->with(['client', 'assignedAgent', 'closedByAgent', 'tags', 'messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->find($this->conversationId);

        return [
            'conversation_id' => $this->conversationId,
            'reason' => $this->reason,
            'conversation' => $conversation
                ? app(ConversationService::class)->toInboxItem($conversation)
                : null,
        ];
    }
}
