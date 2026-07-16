<?php

namespace Application\Services\Bot;

use App\Jobs\SendWhatsAppMessageJob;
use Application\Services\Settings\BusinessHoursService;
use Application\Services\Settings\FeatureFlagService;
use Domain\Shared\Enums\ConversationStatus;
use Domain\Shared\Enums\MessageSenderType;
use Domain\Shared\Enums\MessageStatus;
use Domain\Shared\Enums\MessageType;
use Illuminate\Support\Str;
use Infrastructure\Persistence\Eloquent\Models\BotKnowledge;
use Infrastructure\Persistence\Eloquent\Models\BotTopic;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\Setting;

class BotService
{
    public function __construct(
        private readonly BusinessHoursService $businessHours,
        private readonly FeatureFlagService $features,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) Setting::getValue('ai', 'bot_enabled', config('bot.enabled'));
    }

    public function startConversation(Conversation $conversation): void
    {
        if ($this->features->isEnabled('business_hours_bot', true) && ! $this->businessHours->isOpen()) {
            $this->setBotMeta($conversation, [
                'step' => 'after_hours',
                'topic_id' => null,
                'name_collected' => false,
            ]);
            $this->sendBotMessage($conversation, $this->businessHours->afterHoursMessage());
            $this->escalateToHuman($conversation, sendTransferMessage: false);

            return;
        }
        $conversation->loadMissing('client');
        $knownName = $this->resolveKnownClientName($conversation->client);

        // Cliente já conhecido (mesmo número): pula pedido de nome e mostra menu.
        if ($knownName) {
            $this->setBotMeta($conversation, [
                'step' => 'awaiting_topic',
                'topic_id' => null,
                'name_collected' => true,
                'client_name' => $knownName,
            ]);

            $this->sendTopicsMenu(
                $conversation,
                $knownName,
                $this->welcomeBackMessage($knownName)
            );

            return;
        }

        $this->setBotMeta($conversation, [
            'step' => 'awaiting_name',
            'topic_id' => null,
            'name_collected' => false,
        ]);

        $this->sendBotMessage(
            $conversation,
            Setting::getValue('notifications', 'bot_ask_name_message', config('bot.ask_name_message'))
        );
    }

    public function handleIncoming(Message $clientMessage, Conversation $conversation): void
    {
        if (! $this->isEnabled()) {
            $this->escalateToHuman($conversation);

            return;
        }

        if ($conversation->status !== ConversationStatus::BotActive) {
            return;
        }

        $text = trim($clientMessage->content ?? '');
        $lower = Str::lower($text);
        $interactiveId = $clientMessage->metadata['interactive_id'] ?? null;

        // Mídia sem legenda útil: avisa e sugere atendente / menu
        if (in_array($clientMessage->type?->value, ['image', 'audio', 'video', 'document'], true)
            && in_array($text, ['[imagem]', '[áudio]', '[vídeo]', '[documento]', '[figurinha]', ''], true)
        ) {
            $this->sendBotMessage(
                $conversation,
                "Recebi seu arquivo! 📎\n\nPara eu te ajudar melhor, descreva por texto o que precisa.\nOu digite *atendente* para falar com um humano.\nDigite *menu* para ver os assuntos."
            );

            return;
        }

        if ($this->wantsHuman($lower) || $interactiveId === 'topic_outros') {
            $this->escalateToHuman($conversation);

            return;
        }

        $step = $this->botMeta($conversation)['step'] ?? 'awaiting_name';

        match ($step) {
            'awaiting_name' => $this->handleNameStep($conversation, $text),
            'awaiting_topic' => $this->handleTopicStep($conversation, $text, $interactiveId),
            'answering' => $this->handleAnsweringStep($conversation, $lower),
            default => $this->startConversation($conversation),
        };
    }

    private function handleNameStep(Conversation $conversation, string $text): void
    {
        $name = $this->extractName($text);

        if ($name === null) {
            $this->sendBotMessage(
                $conversation,
                'Não entendi o nome. Pode digitar só o seu nome, por favor? 🙂'
            );

            return;
        }

        $client = $conversation->client;
        if ($client) {
            $client->update(['name' => $name]);
        }

        $this->setBotMeta($conversation, [
            'step' => 'awaiting_topic',
            'topic_id' => null,
            'name_collected' => true,
            'client_name' => $name,
        ]);

        $this->sendTopicsMenu($conversation, $name);
    }

    private function handleTopicStep(Conversation $conversation, string $text, ?string $interactiveId): void
    {
        $topic = $this->resolveTopic($text, $interactiveId);

        if (! $topic) {
            $this->sendBotMessage(
                $conversation,
                "Não encontrei esse assunto. Escolha uma das opções do menu ou digite o número / nome do assunto.\n\nDigite *atendente* se preferir falar com um humano."
            );
            $this->sendTopicsMenu($conversation, $this->botMeta($conversation)['client_name'] ?? null);

            return;
        }

        if ($topic->transfers_to_human || $topic->slug === 'outros') {
            $this->escalateToHuman($conversation);

            return;
        }

        $this->setBotMeta($conversation, [
            'step' => 'answering',
            'topic_id' => $topic->id,
            'name_collected' => true,
            'client_name' => $this->botMeta($conversation)['client_name'] ?? $conversation->client?->name,
        ]);

        $conversation->update(['subject' => $topic->title]);

        $this->sendBotMessage(
            $conversation,
            "Perfeito! Você escolheu *{$topic->title}*.\n\nPode me perguntar o que precisar sobre esse assunto.\n\nSe quiser trocar de tema, digite *menu*.\nPara falar com alguém, digite *atendente*."
        );
    }

    private function handleAnsweringStep(Conversation $conversation, string $lower): void
    {
        if (in_array($lower, ['menu', 'assuntos', 'voltar', 'opcoes', 'opções'], true)) {
            $this->setBotMeta($conversation, [
                'step' => 'awaiting_topic',
                'topic_id' => null,
                'name_collected' => true,
                'client_name' => $this->botMeta($conversation)['client_name'] ?? $conversation->client?->name,
            ]);
            $this->sendTopicsMenu($conversation, $this->botMeta($conversation)['client_name'] ?? null);

            return;
        }

        if ($this->wantsToClose($lower) && $this->hasBotReplied($conversation)) {
            $this->closeByBot($conversation);

            return;
        }

        $topicId = $this->botMeta($conversation)['topic_id'] ?? null;
        $answer = $this->searchKnowledge($lower, $topicId);

        if ($answer) {
            $this->sendBotMessage($conversation, $answer."\n\n_Algo mais? Digite *menu* para outros assuntos ou *atendente* para falar com um humano._");

            return;
        }

        if (config('bot.auto_escalate_on_unknown')) {
            $this->sendBotMessage(
                $conversation,
                'Não encontrei essa resposta na nossa base. Vou te transferir para um atendente, ok?'
            );
            $this->escalateToHuman($conversation, sendTransferMessage: false);

            return;
        }

        $this->sendBotMessage($conversation, config('bot.fallback_message'));
    }

    private function sendTopicsMenu(Conversation $conversation, ?string $name = null, ?string $greeting = null): void
    {
        $topics = BotTopic::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $greeting ??= $name
            ? "Obrigado, *{$name}*! Sobre qual assunto você deseja falar?"
            : 'Sobre qual assunto você deseja falar?';

        $lines = [$greeting, ''];
        $rows = [];

        foreach ($topics as $i => $topic) {
            $n = $i + 1;
            $lines[] = "{$n}. {$topic->title}".($topic->transfers_to_human ? ' (humano)' : '');
            $rows[] = [
                'id' => $topic->transfers_to_human ? 'topic_outros' : $topic->interactiveId(),
                'title' => Str::limit($topic->title, 24, ''),
                'description' => Str::limit($topic->description ?: $topic->title, 72, ''),
            ];
        }

        $lines[] = '';
        $lines[] = 'Toque em uma opção abaixo ou digite o *número* / *nome* do assunto.';

        $interactive = [
            'type' => 'list',
            'header' => ['type' => 'text', 'text' => 'Assuntos'],
            'body' => ['text' => $greeting],
            'footer' => ['text' => 'MGI chat'],
            'action' => [
                'button' => 'Ver assuntos',
                'sections' => [[
                    'title' => 'Escolha uma opção',
                    'rows' => array_slice($rows, 0, 10),
                ]],
            ],
        ];

        $this->sendBotMessage($conversation, implode("\n", $lines), $interactive);
    }

    private function resolveTopic(string $text, ?string $interactiveId): ?BotTopic
    {
        $topics = BotTopic::query()->where('is_active', true)->orderBy('sort_order')->get();

        if ($interactiveId) {
            if ($interactiveId === 'topic_outros') {
                return $topics->firstWhere('transfers_to_human', true)
                    ?? $topics->firstWhere('slug', 'outros');
            }

            if (preg_match('/^topic_(\d+)$/', $interactiveId, $m)) {
                return $topics->firstWhere('id', (int) $m[1]);
            }
        }

        $trimmed = trim($text);
        if (ctype_digit($trimmed)) {
            $index = ((int) $trimmed) - 1;
            if (isset($topics[$index])) {
                return $topics[$index];
            }
        }

        $lower = Str::lower($trimmed);

        return $topics->first(function (BotTopic $topic) use ($lower) {
            return Str::lower($topic->title) === $lower
                || Str::lower($topic->slug) === $lower
                || str_contains(Str::lower($topic->title), $lower)
                || str_contains($lower, Str::lower($topic->title));
        });
    }

    private function searchKnowledge(string $lower, ?int $topicId): ?string
    {
        $query = BotKnowledge::query()
            ->where('is_active', true)
            ->orderBy('sort_order');

        if ($topicId) {
            $query->where('bot_topic_id', $topicId);
        }

        $items = $query->get();

        foreach ($items as $item) {
            $keywords = $item->keywords ?? [];
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($lower, Str::lower($keyword))) {
                    return $item->answer;
                }
            }

            if (str_contains($lower, Str::lower($item->question))) {
                return $item->answer;
            }
        }

        // Fallback: busca global se no tópico não achou
        if ($topicId) {
            return $this->searchKnowledge($lower, null);
        }

        return null;
    }

    private function extractName(string $text): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if ($text === '' || mb_strlen($text) > 60) {
            return null;
        }

        $lower = Str::lower($text);
        $greetings = config('bot.greeting_keywords', []);
        foreach ($greetings as $g) {
            if ($lower === Str::lower($g)) {
                return null;
            }
        }

        // Remove prefixos comuns: "meu nome é João", "sou a Maria"
        $cleaned = preg_replace(
            '/^(meu nome (é|e)|me chamo|eu sou|sou a|sou o|nome[:\s]+)\s*/iu',
            '',
            $text
        ) ?? $text;

        $cleaned = trim($cleaned, " \t\n\r\0\x0B.,!");

        if ($cleaned === '' || mb_strlen($cleaned) < 2) {
            return null;
        }

        if (preg_match('/^[\d\s+\-()]+$/', $cleaned)) {
            return null;
        }

        return Str::title($cleaned);
    }

    public function sendBotMessage(Conversation $conversation, string $content, ?array $interactive = null): Message
    {
        $metadata = ['bot_name' => config('bot.name')];
        if ($interactive) {
            $metadata['interactive'] = $interactive;
        }

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => MessageSenderType::Bot,
            'sender_id' => null,
            'type' => MessageType::Text,
            'content' => $content,
            'status' => MessageStatus::Pending,
            'metadata' => $metadata,
        ]);

        $conversation->update(['last_message_at' => now()]);

        SendWhatsAppMessageJob::dispatch($message->id);

        return $message;
    }

    public function escalateToHuman(Conversation $conversation, bool $sendTransferMessage = true): void
    {
        if (in_array($conversation->status, [ConversationStatus::Waiting, ConversationStatus::InProgress], true)) {
            return;
        }

        $conversation->update([
            'status' => ConversationStatus::Waiting,
            'is_bot_handled' => false,
            'transferred_to_human_at' => now(),
            'waiting_since' => now(),
            'sla_due_at' => now()->addMinutes(15),
            'unread_count' => $conversation->unread_count + 1,
            'metadata' => array_merge($conversation->metadata ?? [], [
                'bot' => array_merge($this->botMeta($conversation), ['step' => 'transferred']),
            ]),
        ]);

        if ($sendTransferMessage) {
            $transferMsg = Setting::getValue('notifications', 'bot_transfer_message', config('bot.transfer_message'));
            $this->sendBotMessage($conversation, $transferMsg);
        }
    }

    public function closeByBot(Conversation $conversation): void
    {
        $closedMsg = Setting::getValue('notifications', 'bot_closed_message', config('bot.closed_message'));
        $this->sendBotMessage($conversation, $closedMsg);

        $conversation->update([
            'status' => ConversationStatus::BotClosed,
            'is_bot_handled' => true,
            'bot_closed_at' => now(),
            'resolved_at' => now(),
            'metadata' => array_merge($conversation->metadata ?? [], [
                'bot' => array_merge($this->botMeta($conversation), ['step' => 'closed']),
            ]),
        ]);
    }

    /** @deprecated Use startConversation */
    public function sendWelcome(Conversation $conversation): void
    {
        $this->startConversation($conversation);
    }

    private function resolveKnownClientName(?Client $client): ?string
    {
        if (! $client) {
            return null;
        }

        $name = trim((string) $client->name);
        if ($name === '') {
            return null;
        }

        $phone = preg_replace('/\D+/', '', (string) $client->phone) ?: '';
        $normalized = preg_replace('/\D+/', '', (string) ($client->phone_normalized ?? '')) ?: '';

        // Nome ainda é o próprio número → trata como desconhecido.
        if ($name === $client->phone || $name === $phone || ($normalized !== '' && $name === $normalized)) {
            return null;
        }

        if (preg_match('/^[\d\s+\-()]+$/', $name)) {
            return null;
        }

        return $name;
    }

    private function welcomeBackMessage(string $name): string
    {
        $template = (string) Setting::getValue(
            'notifications',
            'bot_welcome_back_message',
            config('bot.welcome_back_message')
        );

        return str_replace('{name}', $name, $template);
    }

    private function botMeta(Conversation $conversation): array
    {
        return $conversation->metadata['bot'] ?? [];
    }

    private function setBotMeta(Conversation $conversation, array $bot): void
    {
        $metadata = $conversation->metadata ?? [];
        $metadata['bot'] = array_merge($metadata['bot'] ?? [], $bot);
        $conversation->update(['metadata' => $metadata]);
        $conversation->refresh();
    }

    private function wantsHuman(string $text): bool
    {
        foreach (config('bot.human_transfer_keywords', []) as $keyword) {
            if (str_contains($text, Str::lower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function wantsToClose(string $text): bool
    {
        foreach (config('bot.close_keywords', []) as $keyword) {
            if (str_contains($text, Str::lower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function hasBotReplied(Conversation $conversation): bool
    {
        return $conversation->messages()
            ->where('sender_type', MessageSenderType::Bot)
            ->exists();
    }
}
