# API interna — MGI chat

Base: `/api/internal`  
Auth: middleware `web` + `auth` (cookie de sessão do painel).

CSRF: header `X-CSRF-TOKEN` (já configurado em `resources/js/utils/api.js`).

## Conversas

| Método | Path | Descrição |
|--------|------|-----------|
| GET | `/conversations` | Lista inbox (`status`, `search`, `closed_by`) |
| GET | `/conversations/{id}` | Detalhe + mensagens + notas |
| POST | `/conversations/{id}/messages` | Enviar texto `{content}` ou FormData `image` + `content` |
| POST | `/conversations/{id}/close` | Encerrar (grava `closed_by`) |
| POST | `/conversations/{id}/assign` | Atribuir agente |
| POST | `/conversations/{id}/transfer` | `{agent_id?, department_id?, reason?}` |
| GET | `/conversations/{id}/notes` | Notas internas |
| POST | `/conversations/{id}/notes` | `{body}` |
| DELETE | `/conversation-notes/{id}` | Remover nota |

## Robô / FAQ

| Método | Path |
|--------|------|
| GET | `/bot-knowledge` |
| PUT | `/bot-knowledge/ask-name` |
| POST/PUT/DELETE | `/bot-topics`, `/bot-topics/{id}` |
| POST/PUT/DELETE | `/bot-knowledge-items`, `/{id}` |
| POST | `/bot/simulate` | `{phone, content, name?}` |

## Outros
- Clientes: resource `/clients`  
- Atendentes: `/agents`  
- Settings: `PUT /settings`  

## Webhook público (sem auth sessão)
- `GET|POST /api/webhook/whatsapp`
