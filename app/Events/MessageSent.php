<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Infrastructure\Persistence\Eloquent\Models\Message;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing('attachments');
        $attachment = $this->message->attachments->first();
        $url = $attachment ? Storage::disk('public')->url($attachment->file_path) : null;
        $isImage = $attachment && str_starts_with((string) $attachment->mime_type, 'image/');

        return [
            'id' => $this->message->id,
            'from' => 'agent',
            'type' => $this->message->type?->value ?? 'text',
            'text' => $this->message->content,
            'time' => $this->message->created_at->format('H:i'),
            'status' => $this->message->status->value,
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
