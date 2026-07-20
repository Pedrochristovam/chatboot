# Operação robusta do MGI Chat

## Processos obrigatórios

Em produção, mantenha estes processos ativos:

```bash
php artisan queue:work --tries=20 --timeout=120 --backoff=5
php artisan schedule:work
php artisan reverb:start
```

### Redis (recomendado em produção)

Para reduzir a pressão no MySQL, configure:

```env
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Instale o cliente PHP (`composer require predis/predis`) e um servidor Redis. Em desenvolvimento local, `database`/`file` continuam válidos.

O scheduler registra seu heartbeat, despacha mensagens agendadas, detecta agentes sem heartbeat a cada minuto e remove recibos operacionais antigos. O frontend envia presença a cada **60 segundos** (com skip se o sinal ainda estiver fresco); logout explícito devolve as conversas imediatamente, enquanto quedas abruptas são detectadas após ~150s sem heartbeat.

## Meta Cloud API

Configure `WHATSAPP_META_TOKEN`, `WHATSAPP_META_PHONE_NUMBER_ID`, `WHATSAPP_WEBHOOK_VERIFY_TOKEN` e `WHATSAPP_META_APP_SECRET`, ou informe os valores na tela Configurações. Token, verify token e app secret são armazenados criptografados e nunca são devolvidos ao navegador.

O endpoint `POST /api/webhook/whatsapp` valida `X-Hub-Signature-256` quando há App Secret, grava um recibo leve e responde **202** rapidamente. O parse completo do payload Meta acontece em `ProcessMetaWebhookJob`. Recibos duplicados são ignorados; status recebidos antes da mensagem ficam retidos para reconciliação.

## Performance operacional

- Settings e feature flags são cacheados (TTL ~10 min) e invalidados no update.
- A inbox atualiza de forma incremental via WebSocket; o refetch completo é debounced.
- Mensagens do chat são paginadas (50 por página) com “carregar anteriores”.
- Escopo de departamento/atribuição é aplicado no SQL da inbox.
- Índices compostos cobrem status/SLA/fila e histórico de mensagens.

## Recuperação

- Tela **Operações**: mostra banco, cache, fila, scheduler, Reverb, Meta e mensagens com falha.
- `php artisan messages:retry-failed --dry-run`: lista reenvios seguros.
- `php artisan messages:retry-failed --limit=50`: reenvia somente mensagens sem confirmação da Meta.
- `php artisan agents:requeue-offline`: força a verificação de presença.
- `php artisan operations:prune --days=30`: remove recibos processados e jobs falhos antigos.
- `php artisan queue:failed` e `php artisan queue:retry <id>`: inspecionam e recuperam jobs de infraestrutura.

Mensagens com `whatsapp_message_id` não podem ser reenviadas manualmente, evitando duplicidade. Respostas do bot canceladas por transferência também não podem ser reativadas.

## SLA e fila

O SLA é calculado em minutos úteis, conforme dias e horários em Configurações. A contagem pausa fora do expediente e reinicia quando uma conversa retorna à fila. A inbox prioriza conversas aguardando pelo menor vencimento e expõe os estados normal, próximo do limite e estourado.

## Deploy e validação

```bash
php artisan migrate --force
php artisan optimize
npm ci
npm run build
php artisan test
```

Os testes usam SQLite em memória com um cache de configuração separado. Não remova `APP_CONFIG_CACHE` do `phpunit.xml`, pois ele impede que um cache local faça a suíte apontar para o banco de desenvolvimento.
