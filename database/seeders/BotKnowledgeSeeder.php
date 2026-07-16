<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Infrastructure\Persistence\Eloquent\Models\BotKnowledge;
use Infrastructure\Persistence\Eloquent\Models\BotTopic;

class BotKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $topics = [
            [
                'slug' => 'horario',
                'title' => 'Horário',
                'description' => 'Expediente e funcionamento',
                'sort_order' => 1,
                'knowledge' => [
                    [
                        'question' => 'Qual o horário de atendimento?',
                        'answer' => 'Nosso atendimento humano funciona de segunda a sexta, das 08:00 às 18:00. Fora desse horário o assistente virtual continua disponível.',
                        'keywords' => ['horario', 'horário', 'funciona', 'aberto', 'expediente', 'abre', 'fecha'],
                    ],
                    [
                        'question' => 'Atendem no sábado?',
                        'answer' => 'Não atendemos aos sábados e domingos. O suporte humano retorna na segunda-feira às 08:00.',
                        'keywords' => ['sabado', 'sábado', 'domingo', 'fim de semana', 'fds'],
                    ],
                ],
            ],
            [
                'slug' => 'boletos',
                'title' => 'Boletos',
                'description' => 'Pagamentos e 2ª via',
                'sort_order' => 2,
                'knowledge' => [
                    [
                        'question' => 'Como pedir segunda via de boleto?',
                        'answer' => 'Para emitir a 2ª via, envie o CPF ou o número do pedido. Um atendente confirma os dados e envia o boleto.',
                        'keywords' => ['boleto', 'segunda via', '2 via', 'pagamento', 'pagar', 'fatura'],
                    ],
                    [
                        'question' => 'O boleto venceu, e agora?',
                        'answer' => 'Boletos vencidos podem ser reemitidos. Informe o CPF ou o número do pedido para gerarmos um novo.',
                        'keywords' => ['vencido', 'atrasado', 'venceu', 'multa'],
                    ],
                ],
            ],
            [
                'slug' => 'pedidos',
                'title' => 'Pedidos',
                'description' => 'Entrega e rastreio',
                'sort_order' => 3,
                'knowledge' => [
                    [
                        'question' => 'Como rastrear meu pedido?',
                        'answer' => 'Envie o número do pedido ou o CPF cadastrado que verifico o status da entrega para você.',
                        'keywords' => ['pedido', 'rastreio', 'rastrear', 'entrega', 'codigo', 'código'],
                    ],
                    [
                        'question' => 'Qual o prazo de entrega?',
                        'answer' => 'O prazo médio é de 3 a 7 dias úteis após a confirmação do pagamento, conforme a região.',
                        'keywords' => ['prazo', 'demora', 'quando chega', 'dias'],
                    ],
                ],
            ],
            [
                'slug' => 'planos',
                'title' => 'Planos',
                'description' => 'Preços e comercial',
                'sort_order' => 4,
                'knowledge' => [
                    [
                        'question' => 'Quanto custa?',
                        'answer' => 'Temos planos a partir de R$ 97/mês. Posso te passar um resumo ou transferir para o comercial — digite *atendente*.',
                        'keywords' => ['preco', 'preço', 'valor', 'plano', 'mensalidade', 'quanto custa'],
                    ],
                ],
            ],
            [
                'slug' => 'outros',
                'title' => 'Outros / Atendente',
                'description' => 'Falar com um humano',
                'sort_order' => 99,
                'transfers_to_human' => true,
                'knowledge' => [],
            ],
        ];

        foreach ($topics as $data) {
            $knowledge = $data['knowledge'];
            unset($data['knowledge']);

            $topic = BotTopic::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'sort_order' => $data['sort_order'] ?? 0,
                    'is_active' => true,
                    'transfers_to_human' => $data['transfers_to_human'] ?? false,
                ]
            );

            foreach ($knowledge as $i => $item) {
                BotKnowledge::query()->updateOrCreate(
                    [
                        'bot_topic_id' => $topic->id,
                        'question' => $item['question'],
                    ],
                    [
                        'answer' => $item['answer'],
                        'keywords' => $item['keywords'],
                        'is_active' => true,
                        'sort_order' => $i,
                    ]
                );
            }
        }
    }
}
