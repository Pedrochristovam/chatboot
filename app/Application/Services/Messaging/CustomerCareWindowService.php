<?php

namespace Application\Services\Messaging;

use Application\Services\WhatsApp\WhatsAppConfigService;
use Domain\Shared\Enums\MessageSenderType;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\Message;

class CustomerCareWindowService
{
    public const WINDOW_HOURS = 24;

    public function __construct(
        private readonly WhatsAppConfigService $whatsappConfig,
    ) {}

    public function shouldEnforce(): bool
    {
        return $this->whatsappConfig->driver() === 'meta';
    }

    public function lastClientMessageAt(Conversation $conversation): ?\Illuminate\Support\Carbon
    {
        $message = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_type', MessageSenderType::Client)
            ->orderByDesc('id')
            ->first();

        return $message?->created_at;
    }

    public function isOpen(Conversation $conversation): bool
    {
        $last = $this->lastClientMessageAt($conversation);

        return $last !== null && $last->gt(now()->subHours(self::WINDOW_HOURS));
    }

    public function expiresAt(Conversation $conversation): ?\Illuminate\Support\Carbon
    {
        $last = $this->lastClientMessageAt($conversation);

        return $last?->copy()->addHours(self::WINDOW_HOURS);
    }

    public function assertCanSendSessionMessage(Conversation $conversation): void
    {
        if (! $this->shouldEnforce()) {
            return;
        }

        if (! $this->isOpen($conversation)) {
            throw new \RuntimeException(
                'A janela de 24h do WhatsApp expirou. Envie um template aprovado pela Meta para reabrir a conversa.'
            );
        }
    }

    public function snapshot(Conversation $conversation): array
    {
        $expires = $this->expiresAt($conversation);
        $enforced = $this->shouldEnforce();

        return [
            'enforced' => $enforced,
            'open' => $enforced ? $this->isOpen($conversation) : true,
            'window_hours' => self::WINDOW_HOURS,
            'last_client_message_at' => $this->lastClientMessageAt($conversation)?->toIso8601String(),
            'expires_at' => $expires?->toIso8601String(),
            'expires_at_label' => $expires?->format('d/m/Y H:i'),
        ];
    }
}
