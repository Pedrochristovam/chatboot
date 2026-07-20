<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Infrastructure\Persistence\Eloquent\Models\Message;

class MessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
            new PrivateChannel('inbox'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.received';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing('attachments');
        $attachment = $this->message->attachments->first();
        $url = $attachment ? Storage::disk('public')->url($attachment->file_path) : null;
        $isImage = $attachment && str_starts_with((string) $attachment->mime_type, 'image/');

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'from' => 'client',
            'type' => $this->message->type?->value ?? 'text',
            'text' => $this->message->content,
            'time' => $this->message->created_at->format('H:i'),
            'image_url' => $isImage ? $url : null,
            'attachments' => $attachment ? [[
                'id' => $attachment->id,
                'url' => $url,
                'mime' => $attachment->mime_type,
                'name' => $attachment->file_name,
                'is_image' => $isImage,
            ]] : [],
        ];
    }
}
