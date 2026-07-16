# Arquitetura — MGI chat

## Camadas

### Application (`app/Application`)
Serviços de caso de uso, DTOs e contratos.

- `Services/Conversation/*` — inbox, mensagens, status, transfer, notas  
- `Services/Bot/*` — robô e FAQ  
- `Services/WhatsApp/*` — config Meta, mídia  
- `Services/Settings/*` — settings, horário comercial, feature flags  
- `Contracts/WhatsApp/WhatsAppProviderInterface`  

### Domain (`app/Domain`)
Enums: `ConversationStatus`, `MessageStatus`, `MessageType`, `MessageSenderType`, etc.

### Infrastructure (`app/Infrastructure`)
- Models Eloquent em `Persistence/Eloquent/Models`  
- Providers WhatsApp  
- `Logging/AuditLogger`  

### HTTP
- `Web\*` — páginas Blade autenticadas  
- `Api\*` — JSON para o painel (`/api/internal`) + webhook público  

### Jobs
| Job | Responsabilidade |
|-----|------------------|
| `ProcessIncomingWhatsAppJob` | Mensagem inbound |
| `SendWhatsAppMessageJob` | Envio texto/imagem |
| `ProcessWhatsAppStatusJob` | Status Meta (delivered/read/failed) |

### Events (broadcast)
- `MessageReceived` → canais `conversation.{id}` e `inbox`  
- `MessageSent` → `conversation.{id}`  

Canais autorizados em `routes/channels.php`.

## Fluxo de uma mensagem do cliente

1. `POST /api/webhook/whatsapp`  
2. Se payload tem `statuses[]` → `ProcessWhatsAppStatusJob`  
3. Senão → `provider->receiveWebhook()` → DTO → `ProcessIncomingWhatsAppJob`  
4. `MessageService::processIncoming` cria/atualiza client + conversation + message  
5. Se mídia → `WhatsAppMediaService::attachFromIncoming`  
6. Se `bot_active` → `BotService`  
7. Evento `MessageReceived`  

## Fluxo de resposta do atendente

1. `POST /api/internal/conversations/{id}/messages`  
2. Texto ou `multipart` (`image`)  
3. Persiste message (+ attachment)  
4. `SendWhatsAppMessageJob`  
5. Provider `sendMessage` / `sendImage`  
6. Evento `MessageSent`  

## Status de conversa

| Status | Significado |
|--------|-------------|
| `bot_active` | Robô atendendo |
| `bot_closed` | Encerrada pelo robô |
| `waiting` | Fila humana |
| `in_progress` | Atendente ativo |
| `closed` | Encerrada por humano |
| `resolved` | Resolvida (enum reservado) |

Inbox “Conversas” exclui bot e encerradas.
