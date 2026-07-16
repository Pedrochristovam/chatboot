<?php

namespace App\Console\Commands;

use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\Services\Conversation\MessageService;
use Domain\Shared\Enums\ConversationStatus;
use Domain\Shared\Enums\MessageSenderType;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\Message;

/**
 * Simula um cliente WhatsApp sem número real / Meta.
 * Usa o mesmo fluxo do webhook (bot → FAQ → atendente).
 */
class SimulateWhatsAppChatCommand extends Command
{
    protected $signature = 'whatsapp:simulate
        {--phone=5511999990001 : Número falso do cliente}
        {--name=Cliente Teste : Nome do perfil WhatsApp}';

    protected $description = 'Simula conversa com o bot (sem WhatsApp real)';

    public function handle(MessageService $messageService): int
    {
        $phone = preg_replace('/\D+/', '', (string) $this->option('phone')) ?: '5511999990001';
        $name = (string) $this->option('name');

        $this->newLine();
        $this->info('=== Simulador WhatsApp (modo local) ===');
        $this->line("Cliente: {$name} ({$phone})");
        $this->line('Digite mensagens como se fosse o WhatsApp.');
        $this->line('Comandos: /sair  |  /historico  |  /reset');
        $this->newLine();
        $this->comment('Sugestão de fluxo: oi → seu nome → número do assunto → pergunta → atendente');
        $this->newLine();

        while (true) {
            $text = $this->ask('Você (cliente)');

            if ($text === null) {
                continue;
            }

            $text = trim($text);

            if ($text === '' || $text === '/sair' || strtolower($text) === 'sair') {
                $this->info('Simulação encerrada.');
                break;
            }

            if ($text === '/historico') {
                $this->printHistory($phone);
                continue;
            }

            if ($text === '/reset') {
                $this->resetConversation($phone);
                $this->warn('Conversa anterior encerrada. Próxima mensagem abre conversa nova com o bot.');
                continue;
            }

            $dto = new IncomingMessageDTO(
                from: $phone,
                messageId: 'sim_'.Str::uuid()->toString(),
                type: 'text',
                content: $text,
                metadata: [
                    'contact_name' => $name,
                    'pushName' => $name,
                    'simulated' => true,
                ],
            );

            $messageService->processIncoming($dto);

            // Mostra respostas do bot (e agente, se houver) geradas nesta rodada
            $this->printLatestBotReplies($phone);
        }

        $this->newLine();
        $this->line('Veja também no painel: Conversas / Encerradas pelo robô');
        $this->line('URL: '.rtrim((string) config('app.url'), '/').'/conversations');

        return self::SUCCESS;
    }

    private function printLatestBotReplies(string $phone): void
    {
        $client = Client::query()->where('phone', $phone)->orWhere('phone_normalized', $phone)->first();
        if (! $client) {
            return;
        }

        $conversation = Conversation::query()
            ->where('client_id', $client->id)
            ->latest('id')
            ->first();

        if (! $conversation) {
            return;
        }

        $replies = Message::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('sender_type', [MessageSenderType::Bot, MessageSenderType::Agent])
            ->where('created_at', '>=', now()->subSeconds(5))
            ->orderBy('id')
            ->get();

        if ($replies->isEmpty()) {
            // fallback: últimas 2 do bot na conversa
            $replies = Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('sender_type', MessageSenderType::Bot)
                ->latest('id')
                ->limit(2)
                ->get()
                ->reverse();
        }

        foreach ($replies as $msg) {
            $who = $msg->sender_type === MessageSenderType::Bot ? 'Bot' : 'Atendente';
            $this->newLine();
            $this->info("{$who}:");
            $this->line($msg->content ?? '');
        }

        $this->newLine();
        $this->comment('Status da conversa: '.$conversation->fresh()->status->value);
    }

    private function printHistory(string $phone): void
    {
        $client = Client::query()->where('phone', $phone)->orWhere('phone_normalized', $phone)->first();
        if (! $client) {
            $this->warn('Ainda não há histórico.');
            return;
        }

        $conversation = Conversation::query()
            ->where('client_id', $client->id)
            ->latest('id')
            ->first();

        if (! $conversation) {
            $this->warn('Sem conversa.');
            return;
        }

        $this->newLine();
        $this->table(
            ['Quem', 'Mensagem', 'Hora'],
            Message::query()
                ->where('conversation_id', $conversation->id)
                ->orderBy('id')
                ->get()
                ->map(fn (Message $m) => [
                    match ($m->sender_type) {
                        MessageSenderType::Client => 'Cliente',
                        MessageSenderType::Bot => 'Bot',
                        default => 'Atendente',
                    },
                    Str::limit($m->content ?? '', 80),
                    $m->created_at?->format('H:i:s'),
                ])
                ->all()
        );
    }

    private function resetConversation(string $phone): void
    {
        $client = Client::query()->where('phone', $phone)->orWhere('phone_normalized', $phone)->first();
        if (! $client) {
            return;
        }

        Conversation::query()
            ->where('client_id', $client->id)
            ->whereIn('status', [
                ConversationStatus::BotActive,
                ConversationStatus::Waiting,
                ConversationStatus::InProgress,
            ])
            ->update([
                'status' => ConversationStatus::Closed,
                'closed_at' => now(),
                'is_bot_handled' => false,
            ]);
    }
}
