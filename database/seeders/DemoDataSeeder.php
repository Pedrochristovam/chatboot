<?php

namespace Database\Seeders;

use Domain\Shared\Enums\ClientStatus;
use Domain\Shared\Enums\ConversationOrigin;
use Domain\Shared\Enums\ConversationStatus;
use Domain\Shared\Enums\MessageSenderType;
use Domain\Shared\Enums\MessageStatus;
use Domain\Shared\Enums\MessageType;
use Illuminate\Database\Seeder;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\Department;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\Tag;
use Infrastructure\Persistence\Eloquent\Models\User;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@chatflow.com')->first();
        $suporte = Department::query()->where('slug', 'suporte')->first();
        $vipTag = Tag::query()->where('slug', 'vip')->first();
        $urgenteTag = Tag::query()->where('slug', 'urgente')->first();
        $leadTag = Tag::query()->where('slug', 'lead')->first();

        $clientsData = [
            ['name' => 'Mariana Oliveira', 'phone' => '5511987654321', 'email' => 'mariana@email.com', 'company' => 'Tech Corp', 'notes' => 'Cliente prefere contato pela manhã. Interessada no plano Enterprise.'],
            ['name' => 'Lucas Peixoto', 'phone' => '5521998765432', 'email' => 'lucas@email.com', 'company' => 'Startup IO'],
            ['name' => 'Camila Torres', 'phone' => '5531976543210', 'email' => 'camila@email.com', 'company' => 'Design Co'],
            ['name' => 'Felipe Mendes', 'phone' => '5541965432109', 'email' => 'felipe@email.com', 'company' => 'Corp Ltda'],
            ['name' => 'Ricardo Oliveira', 'phone' => '5511912345678', 'email' => 'ricardo@email.com', 'company' => 'Solutions SA'],
            ['name' => 'Ana Carolina', 'phone' => '5521988877766', 'email' => 'ana@startup.io', 'company' => 'Startup IO'],
            ['name' => 'Pedro Henrique', 'phone' => '5531966655544', 'email' => 'pedro@corp.com', 'company' => 'Corp Ltda'],
            ['name' => 'Juliana Martins', 'phone' => '5541955544433', 'email' => 'ju@design.co', 'company' => 'Design Co'],
        ];

        foreach ($clientsData as $i => $data) {
            $client = Client::query()->firstOrCreate(
                ['phone' => $data['phone']],
                array_merge($data, ['status' => ClientStatus::Active, 'last_contact_at' => now()->subHours($i * 3)])
            );

            if ($i === 0 && $vipTag) {
                $client->tags()->syncWithoutDetaching([$vipTag->id]);
            }
            if ($i === 2 && $urgenteTag) {
                $client->tags()->syncWithoutDetaching([$urgenteTag->id]);
            }
            if ($i === 5 && $leadTag) {
                $client->tags()->syncWithoutDetaching([$leadTag->id]);
            }

            $status = match ($i % 3) {
                0 => ConversationStatus::InProgress,
                1 => ConversationStatus::Waiting,
                default => ConversationStatus::Closed,
            };

            $conversation = Conversation::query()->firstOrCreate(
                ['client_id' => $client->id, 'status' => $status],
                [
                    'assigned_to' => $admin?->id,
                    'department_id' => $suporte?->id,
                    'origin' => ConversationOrigin::Whatsapp,
                    'last_message_at' => now()->subMinutes($i * 15),
                    'unread_count' => $i % 2 === 0 ? 2 : 0,
                    'first_response_at' => now()->subMinutes($i * 15 + 5),
                    'closed_at' => $status === ConversationStatus::Closed ? now()->subDay() : null,
                ]
            );

            if ($conversation->messages()->count() === 0) {
                $messages = [
                    ['sender' => MessageSenderType::Client, 'content' => 'Olá, preciso de ajuda!', 'mins' => 30],
                    ['sender' => MessageSenderType::Agent, 'content' => 'Olá! Como posso ajudar?', 'mins' => 28],
                    ['sender' => MessageSenderType::Client, 'content' => 'Tenho uma dúvida sobre meu pedido.', 'mins' => 25],
                ];

                if ($i === 0) {
                    $messages = [
                        ['sender' => MessageSenderType::Client, 'content' => 'Olá, preciso de ajuda com o boleto do mês passado.', 'mins' => 30],
                        ['sender' => MessageSenderType::Agent, 'content' => 'Olá Mariana! Claro, vou verificar para você agora mesmo.', 'mins' => 28],
                        ['sender' => MessageSenderType::Client, 'content' => 'Obrigada! O vencimento era dia 10.', 'mins' => 25],
                        ['sender' => MessageSenderType::Agent, 'content' => 'Encontrei o boleto. Vou reenviar para o seu e-mail cadastrado.', 'mins' => 22],
                        ['sender' => MessageSenderType::Client, 'content' => 'Perfeito, aguardo!', 'mins' => 20],
                    ];
                }

                foreach ($messages as $msg) {
                    Message::query()->create([
                        'conversation_id' => $conversation->id,
                        'sender_type' => $msg['sender'],
                        'sender_id' => $msg['sender'] === MessageSenderType::Agent ? $admin?->id : $client->id,
                        'type' => MessageType::Text,
                        'content' => $msg['content'],
                        'status' => MessageStatus::Delivered,
                        'created_at' => now()->subMinutes($msg['mins']),
                        'updated_at' => now()->subMinutes($msg['mins']),
                    ]);
                }
            }
        }

        $this->seedBotConversations();
    }

    private function seedBotConversations(): void
    {
        $botClients = [
            ['name' => 'Roberto Silva', 'phone' => '5511988112233'],
            ['name' => 'Fernanda Lima', 'phone' => '5521988223344'],
        ];

        foreach ($botClients as $i => $data) {
            $client = Client::query()->firstOrCreate(
                ['phone' => $data['phone']],
                array_merge($data, ['status' => ClientStatus::Active, 'last_contact_at' => now()->subHours(2)])
            );

            $status = $i === 0 ? ConversationStatus::BotClosed : ConversationStatus::BotActive;

            $conversation = Conversation::query()->firstOrCreate(
                ['client_id' => $client->id, 'status' => $status],
                [
                    'origin' => ConversationOrigin::Whatsapp,
                    'is_bot_handled' => true,
                    'last_message_at' => now()->subMinutes(10),
                    'bot_closed_at' => $status === ConversationStatus::BotClosed ? now()->subMinutes(5) : null,
                    'resolved_at' => $status === ConversationStatus::BotClosed ? now()->subMinutes(5) : null,
                ]
            );

            if ($conversation->messages()->count() === 0) {
                $msgs = $i === 0
                    ? [
                        ['sender' => MessageSenderType::Client, 'content' => 'Qual o horário de atendimento?', 'mins' => 20],
                        ['sender' => MessageSenderType::Bot, 'content' => 'Nosso horário de atendimento humano é de segunda a sexta, das 08:00 às 18:00.', 'mins' => 19],
                        ['sender' => MessageSenderType::Client, 'content' => 'Obrigado!', 'mins' => 18],
                        ['sender' => MessageSenderType::Bot, 'content' => 'Fico feliz em ter ajudado! Se precisar de mais algo, envie uma nova mensagem.', 'mins' => 17],
                    ]
                    : [
                        ['sender' => MessageSenderType::Client, 'content' => 'Oi, quero saber sobre meu pedido', 'mins' => 8],
                        ['sender' => MessageSenderType::Bot, 'content' => 'Olá! Para rastrear seu pedido, envie o número do pedido ou CPF cadastrado.', 'mins' => 7],
                        ['sender' => MessageSenderType::Client, 'content' => 'Pedido #12345', 'mins' => 5],
                        ['sender' => MessageSenderType::Bot, 'content' => 'Para rastrear seu pedido, envie o número do pedido ou CPF cadastrado.', 'mins' => 4],
                    ];

                foreach ($msgs as $msg) {
                    Message::query()->create([
                        'conversation_id' => $conversation->id,
                        'sender_type' => $msg['sender'],
                        'sender_id' => $msg['sender'] === MessageSenderType::Client ? $client->id : null,
                        'type' => MessageType::Text,
                        'content' => $msg['content'],
                        'status' => MessageStatus::Delivered,
                        'created_at' => now()->subMinutes($msg['mins']),
                        'updated_at' => now()->subMinutes($msg['mins']),
                    ]);
                }
            }
        }
    }
}
