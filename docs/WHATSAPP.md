# WhatsApp / Meta Cloud API

## Drivers

| `WHATSAPP_DRIVER` | Comportamento |
|-------------------|---------------|
| `null` | Stub: não chama Meta; útil em local |
| `meta` | Cloud API Graph v21 |

Config também pode vir da tabela `settings` (grupo `whatsapp`) e sobrescrever o `.env`.

## Webhook

| Método | URL | Uso |
|--------|-----|-----|
| GET | `/api/webhook/whatsapp` | Verificação (`hub.challenge`) |
| POST | `/api/webhook/whatsapp` | Mensagens + statuses |

Subscribe fields sugeridos na Meta: `messages`.

### Mensagens
Parse em `MetaCloudProvider::parseMetaPayload` → DTO com `mediaId` quando houver imagem/documento/áudio/vídeo.

### Status
Payload `entry[].changes[].value.statuses[]` → `ProcessWhatsAppStatusJob` → atualiza `messages.status`, `delivered_at`, `read_at`, `message_status_events`.

## Envio de imagem
1. Arquivo em disco `public`  
2. `MetaCloudProvider::uploadMedia` (multipart)  
3. Send `type=image` com `id` (ou `link` como fallback)  

## Túnel local
Para Meta alcançar seu PC:
```bash
# exemplo ngrok
ngrok http 8888
# APP_URL=https://xxxx.ngrok-free.app
```

## Segurança
- Guarde o token só no `.env` / settings criptografados  
- Configure `WHATSAPP_META_APP_SECRET` para validar `X-Hub-Signature-256`
- Use HTTPS em produção  
- Mantenha `queue:work` sempre ativo  
