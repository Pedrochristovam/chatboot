<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Infrastructure\Persistence\Eloquent\Models\Message;

class MessageStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.'.$this->message->conversation_id)];
    }

    public function broadcastAs(): string
    {
        return 'message.status';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'status' => $this->message->status->value,
            'sent_at' => $this->message->sent_at?->toIso8601String(),
            'delivered_at' => $this->message->delivered_at?->toIso8601String(),
            'read_at' => $this->message->read_at?->toIso8601String(),
            'error' => $this->message->error_message,
        ];
    }
}
