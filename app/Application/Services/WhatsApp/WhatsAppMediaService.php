<?php

namespace Application\Services\WhatsApp;

use Application\Contracts\WhatsApp\WhatsAppProviderInterface;
use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Infrastructure\Persistence\Eloquent\Models\Attachment;
use Infrastructure\Persistence\Eloquent\Models\Message;

class WhatsAppMediaService
{
    public function __construct(
        private readonly WhatsAppProviderInterface $provider,
    ) {}

    public function attachFromIncoming(Message $message, IncomingMessageDTO $dto): ?Attachment
    {
        if (! in_array($dto->type, ['image', 'document', 'audio', 'video', 'sticker'], true)) {
            return null;
        }

        $binary = $this->provider->fetchMedia(
            mediaId: $dto->mediaId,
            mediaUrl: $dto->mediaUrl,
        );

        if (! $binary) {
            return null;
        }

        $mime = $dto->mediaMimeType ?: ($binary['mime_type'] ?? 'application/octet-stream');
        $extension = $this->extensionFromMime($mime, $dto->fileName ?: ($binary['file_name'] ?? null));
        $fileName = $dto->fileName ?: ($binary['file_name'] ?? ('arquivo.'.$extension));
        $path = sprintf(
            'whatsapp/%d/%s.%s',
            $message->conversation_id,
            Str::uuid()->toString(),
            $extension
        );

        Storage::disk('public')->put($path, $binary['contents']);

        return Attachment::query()->create([
            'message_id' => $message->id,
            'file_name' => $fileName,
            'file_path' => $path,
            'mime_type' => $mime,
            'file_size' => strlen($binary['contents']),
            'created_at' => now(),
        ]);
    }

    public function attachUploadedImage(Message $message, UploadedFile $file): Attachment
    {
        $path = $file->store(
            'whatsapp/'.$message->conversation_id,
            'public'
        );

        return Attachment::query()->create([
            'message_id' => $message->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'image/jpeg',
            'file_size' => $file->getSize() ?: 0,
            'created_at' => now(),
        ]);
    }

    public function publicUrl(?Attachment $attachment): ?string
    {
        if (! $attachment?->file_path) {
            return null;
        }

        return Storage::disk('public')->url($attachment->file_path);
    }

    public function absolutePublicUrl(?Attachment $attachment): ?string
    {
        $relative = $this->publicUrl($attachment);
        if (! $relative) {
            return null;
        }

        if (str_starts_with($relative, 'http://') || str_starts_with($relative, 'https://')) {
            return $relative;
        }

        return rtrim((string) config('app.url'), '/').$relative;
    }

    /** @return array{url: string, mime: string, name: string, is_image: bool, kind: string}|null */
    public function serializeAttachment(Attachment $attachment): array
    {
        $url = $this->publicUrl($attachment) ?? '';
        $mime = (string) $attachment->mime_type;

        return [
            'id' => $attachment->id,
            'url' => $url,
            'mime' => $mime,
            'name' => $attachment->file_name,
            'is_image' => str_starts_with($mime, 'image/'),
            'kind' => match (true) {
                str_starts_with($mime, 'image/') => 'image',
                str_starts_with($mime, 'audio/') => 'audio',
                str_starts_with($mime, 'video/') => 'video',
                default => 'document',
            },
        ];
    }

    private function extensionFromMime(string $mime, ?string $fileName): string
    {
        if ($fileName && str_contains($fileName, '.')) {
            return strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) ?: 'bin';
        }

        return match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'audio/ogg', 'audio/opus' => 'ogg',
            'audio/mpeg' => 'mp3',
            'video/mp4' => 'mp4',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
    }
}
