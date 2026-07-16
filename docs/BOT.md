# Robô (Bot) — MGI chat

## Objetivo
Automatizar o primeiro atendimento no WhatsApp com menu de assuntos e FAQ por palavras-chave, escalando para humano quando necessário.

## Passos do fluxo

1. **Saudação** (`greeting_keywords` em `config/bot.php`)  
2. **Nome** — se cliente novo; se já conhecido → mensagem de retorno com `{name}`  
3. **Menu** — `bot_topics` ativos ordenados  
4. **Assunto** — escolha por lista Meta, número ou texto  
5. **FAQ** — match de keywords em `bot_knowledge`  
6. **Humano** — keywords (`atendente`…) ou tópico `transfers_to_human`  
7. **Encerrar** — close keywords após o bot ter respondido  

## Configuração no painel

Rota: `/bot-knowledge`

- Parte A: pedir nome + welcome back  
- Parte B: assuntos  
- Parte C: FAQs do assunto selecionado  

## Tabelas
- `bot_topics` — menu  
- `bot_knowledge` — perguntas/respostas/keywords  
- Settings: `bot_ask_name_message`, `bot_welcome_back_message`, `bot_transfer_message`, `bot_closed_message`  

## Horário comercial
Se feature `business_hours_bot` estiver ligada e estiver fora do horário (`BusinessHoursService`), o bot envia `after_hours_message` e coloca a conversa em `waiting`.

## Teste local
```bash
php artisan whatsapp:simulate
```
Ou API: `POST /api/internal/bot/simulate`.
