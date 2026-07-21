# MGI Chat — WhatsApp CRM + Robô de Atendimento

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-opcional-DC382D?logo=redis&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-compose-2496ED?logo=docker&logoColor=white)
![Tests](https://img.shields.io/badge/tests-19%20passing-brightgreen)
![License](https://img.shields.io/badge/license-Proprietary-lightgrey)

**MGI Chat** (ChatFlow CRM) é um sistema completo de atendimento via **WhatsApp** com inbox humana, robô FAQ, templates Meta, janela de 24h, filas confiáveis, auditoria e painel operacional.

**Repositório:** [github.com/Pedrochristovam/chatboot](https://github.com/Pedrochristovam/chatboot)

---

## Índice

1. [Visão geral](#1-visão-geral)
2. [Destaques recentes](#2-destaques-recentes)
3. [Stack técnica](#3-stack-técnica)
4. [Arquitetura](#4-arquitetura)
5. [Funcionalidades](#5-funcionalidades)
6. [Pré-requisitos](#6-pré-requisitos)
7. [Instalação local](#7-instalação-local)
8. [Docker Compose](#8-docker-compose)
9. [Variáveis de ambiente](#9-variáveis-de-ambiente)
10. [Como rodar no dia a dia](#10-como-rodar-no-dia-a-dia)
11. [Fluxo do robô](#11-fluxo-do-robô)
12. [WhatsApp / Meta Cloud API](#12-whatsapp--meta-cloud-api)
13. [Janela de 24h e templates](#13-janela-de-24h-e-templates)
14. [Mídia](#14-mídia)
15. [APIs internas](#15-apis-internas)
16. [Banco de dados](#16-banco-de-dados)
17. [Papéis e permissões](#17-papéis-e-permissões)
18. [Performance e robustez](#18-performance-e-robustez)
19. [Operações, filas e health](#19-operações-filas-e-health)
20. [Testes](#20-testes)
21. [CI / GitHub Actions](#21-ci--github-actions)
22. [Deploy / produção](#22-deploy--produção)
23. [Estrutura de pastas](#23-estrutura-de-pastas)
24. [Documentação adicional](#24-documentação-adicional)
25. [Credenciais padrão](#25-credenciais-padrão)
26. [Roadmap](#26-roadmap)
27. [Licença](#27-licença)
28. [Suporte](#28-suporte)

---

## 1. Visão geral

O cliente fala no WhatsApp → o webhook chega no Laravel → a mensagem é enfileirada → o **robô** responde ou a conversa vai para a **fila humana** → o atendente responde pelo painel → o job envia de volta pelo provedor WhatsApp.

```
WhatsApp ──webhook──► Laravel API ──queue──► MessageService / BotService
                                              │
                                              ├── salva messages + attachments
                                              ├── responde (bot) ou waiting (humano)
                                              └── SendWhatsAppMessageJob ──► Meta / stub
```

| Item | Valor |
|------|--------|
| Marca visual | Bordo sólido `#8B1E3F` |
| Fuso | `America/Sao_Paulo` |
| App local (Windows) | `http://127.0.0.1:8888` |
| Login seed | `admin@chatflow.com` / `password` |

---

## 2. Destaques recentes

### Experiência do atendente
- **Assumir** conversa da fila
- **Transferir** para agente/departamento
- **Notas internas** no painel lateral
- **Agendar** mensagem (UI no chat + página `/scheduled-messages`)
- **Templates Meta** para reabrir conversa fora da janela de 24h
- Preview de **áudio / vídeo / documento**
- Toasts, retry e banner quando a API falha
- Inbox com **paginação** (carregar mais além de 50)

### Segurança
- Rate limit no login (5 tentativas / 60s)
- Permissões nas rotas web do painel
- Webhook Meta com throttle
- App Secret **obrigatório em produção**

### Confiabilidade / performance
- Presença: heartbeat 60s + **offline no fechamento da aba**
- Webhook fast-ack (202) + job de parse
- Cache de settings/feature flags
- Índices e escopo SQL da inbox
- Painel de **Operações** com fila, Redis, Reverb, falhas e **audit log**
- `docker-compose` com MySQL, Redis, app, queue, scheduler e phpMyAdmin

---

## 3. Stack técnica

| Camada | Tecnologia |
|--------|------------|
| Backend | PHP 8.2+, Laravel 12 |
| Banco | MySQL 8 (`chatflow_crm`) |
| Cache / fila (prod) | Redis (recomendado) ou database |
| Front | Blade + Alpine.js + Tailwind CSS 4 + Vite |
| Filas | `php artisan queue:work` (Horizon opcional) |
| Tempo real | Laravel Reverb + Echo (opcional; fallback poll) |
| WhatsApp | Meta Cloud API (`meta`) ou stub (`null`) |
| Auth | Sessão web (+ Sanctum disponível) |
| Contêineres | Docker Compose |

---

## 4. Arquitetura

```
app/
├── Application/          # Casos de uso (Services, DTOs, Contracts)
├── Domain/               # Enums e regras de domínio
├── Infrastructure/       # Eloquent, WhatsApp providers, AuditLogger
├── Http/Controllers/     # Web + Api
├── Jobs/                 # Webhook, SendWhatsApp, Status
└── Events/               # Broadcast (inbox / conversation)
```

**Provedores WhatsApp** (`Infrastructure/WhatsApp`):

| Provider | Uso |
|----------|-----|
| `NullWhatsAppProvider` | Dev / simulação |
| `MetaCloudProvider` | Produção (Cloud API) |
| Evolution / Z-API / Baileys | Stubs / extensão futura |

Documentação: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)

---

## 5. Funcionalidades

### Painel
| Rota | Descrição |
|------|-----------|
| `/dashboard` | KPIs do dia |
| `/conversations` | Inbox ativa |
| `/closed-conversations` | Encerradas pelo atendente |
| `/bot-conversations` | Encerradas pelo robô |
| `/bot-knowledge` | FAQ, menu, saudação |
| `/clients` | Cadastro de clientes |
| `/agents` | Atendentes |
| `/reports` | Relatórios |
| `/scheduled-messages` | Lista de agendamentos |
| `/operations` | Health, filas, falhas, audit log |
| `/settings` | Configurações (incl. Meta) |

### Robô
1. Cumprimento → pede nome (ou “de volta”)
2. Menu de assuntos (`bot_topics`)
3. FAQs por palavras-chave (`bot_knowledge`)
4. Transferência para humano
5. Encerramento por agradecimento / tchau

### Humanos (inbox)
- Texto e imagem
- Assumir / transferir / notas / agendar / template
- Encerrar → “Encerradas por mim”
- SLA e janela de 24h visíveis no painel

---

## 6. Pré-requisitos

- PHP 8.2+ (`mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`)
- Composer 2
- Node.js 20+ e npm
- MySQL 8
- (Opcional) Redis, Docker, ngrok/Cloudflare Tunnel para webhook Meta

---

## 7. Instalação local

```bash
git clone https://github.com/Pedrochristovam/chatboot.git
cd chatboot

composer install
cp .env.example .env
php artisan key:generate

# Ajuste DB_* no .env
php artisan migrate --seed

npm install
npm run build   # ou: npm run dev

php artisan storage:link
```

Crie o banco:

```sql
CREATE DATABASE chatflow_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 8. Docker Compose

Sobe app, worker, scheduler, MySQL, Redis e phpMyAdmin:

```bash
docker compose up -d --build
```

| Serviço | URL / porta |
|---------|-------------|
| App | `http://localhost:8888` |
| phpMyAdmin | `http://localhost:8081` |
| MySQL | `localhost:3307` |
| Redis | `localhost:6379` |

Variáveis tipicamente usadas no Compose:

```env
DB_HOST=mysql
REDIS_HOST=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

---

## 9. Variáveis de ambiente

Principais chaves (veja `.env.example`):

| Variável | Descrição |
|----------|-----------|
| `APP_URL` | URL pública (ex.: `http://127.0.0.1:8888`) |
| `APP_TIMEZONE` | `America/Sao_Paulo` |
| `DB_*` | MySQL |
| `QUEUE_CONNECTION` | `database` (local) / `redis` (prod) |
| `CACHE_STORE` | `database` / `redis` |
| `SESSION_DRIVER` | `database` / `redis` |
| `WHATSAPP_DRIVER` | `null` ou `meta` |
| `WHATSAPP_META_TOKEN` | Token Cloud API |
| `WHATSAPP_META_PHONE_NUMBER_ID` | Phone Number ID |
| `WHATSAPP_META_APP_SECRET` | Valida `X-Hub-Signature-256` |
| `WHATSAPP_WEBHOOK_VERIFY_TOKEN` | Verify do webhook |
| `BOT_ENABLED` | Liga/desliga robô |
| `BROADCAST_CONNECTION` | `log` ou `reverb` |
| `REVERB_*` / `VITE_REVERB_*` | Tempo real |

**Nunca versione o arquivo `.env`.** Tokens sensíveis também podem ser gravados criptografados em Configurações.

---

## 10. Como rodar no dia a dia

Em **3 terminais** (mínimo no Windows):

```bash
# 1) App — use php -S se artisan serve falhar em portas
php -S 127.0.0.1:8888 -t public

# 2) Fila (obrigatória para WhatsApp / webhooks)
php artisan queue:work --sleep=1 --tries=20 --timeout=120

# 3) Front (dev de JS/CSS)
npm run dev
```

Opcional:

```bash
php artisan schedule:work   # agendamentos + presença offline
php artisan reverb:start    # tempo real
```

Acesse: **http://127.0.0.1:8888/login**

> O Vite (ex.: `:5174`) só serve assets. O sistema é o Laravel na porta **8888**.

---

## 11. Fluxo do robô

Configuração: **Robô & FAQ** (`/bot-knowledge`).

| Parte | Função |
|-------|--------|
| A | Pedido de nome + mensagem de retorno (`{name}`) |
| B | Assuntos do menu |
| C | FAQs com palavras-chave |

Comandos do cliente: `menu`, `atendente`, `obrigado`, `tchau`…

Detalhes: [`docs/BOT.md`](docs/BOT.md)

---

## 12. WhatsApp / Meta Cloud API

### Desenvolvimento
```env
WHATSAPP_DRIVER=null
```

### Produção
```env
WHATSAPP_DRIVER=meta
WHATSAPP_META_TOKEN=...
WHATSAPP_META_PHONE_NUMBER_ID=...
WHATSAPP_META_APP_SECRET=...
WHATSAPP_WEBHOOK_VERIFY_TOKEN=chatflow_webhook_secret
```

| Endpoint | Uso |
|----------|-----|
| `GET /api/webhook/whatsapp` | Verify (hub challenge) |
| `POST /api/webhook/whatsapp` | Receive (throttle + assinatura) |

Fluxo do receive:

1. Valida assinatura (obrigatória em `production` se secret configurado)
2. Grava `webhook_receipts` (idempotência)
3. Responde **202**
4. `ProcessMetaWebhookJob` faz o parse

Guia: [`docs/WHATSAPP.md`](docs/WHATSAPP.md)

---

## 13. Janela de 24h e templates

Com `WHATSAPP_DRIVER=meta`, mensagens de sessão (texto/imagem) só são aceitas se o **último texto do cliente** tiver menos de 24 horas.

Fora da janela:
- O painel mostra aviso
- Use o botão **Template** (nome aprovado no Business Manager + idioma + parâmetros)

API:

```http
POST /api/internal/conversations/{id}/templates
{
  "template_name": "hello_world",
  "language": "pt_BR",
  "body_parameters": ["Maria"]
}
```

---

## 14. Mídia

| Direção | Comportamento |
|---------|----------------|
| Cliente → painel | Download Meta → `storage/app/public/whatsapp/...` → preview |
| Atendente → cliente | Upload local → job → upload Meta ou stub |

- Imagens: jpeg/png/webp/gif até **5 MB**
- Áudio / vídeo / docs: player ou link no thread

---

## 15. APIs internas

Prefixo autenticado (sessão + CSRF): `/api/internal/...`

| Método | Rota | Uso |
|--------|------|-----|
| GET | `/conversations` | Inbox (`limit`, `offset`, `status`, `search`) |
| GET | `/conversations/lookup` | Agentes + departamentos |
| GET | `/conversations/{id}` | Detalhe + mensagens + notes + care_window |
| POST | `/conversations/{id}/messages` | Texto ou imagem |
| POST | `/conversations/{id}/templates` | Template Meta |
| POST | `/conversations/{id}/assign` | Assumir / atribuir |
| POST | `/conversations/{id}/transfer` | Transferir |
| POST | `/conversations/{id}/close` | Encerrar |
| GET/POST | `/conversations/{id}/notes` | Notas internas |
| POST | `/conversations/{id}/scheduled-messages` | Agendar |
| DELETE | `/scheduled-messages/{id}` | Cancelar agendamento |
| POST | `/presence/heartbeat` | Presença |
| POST | `/presence/offline` | Offline imediato |
| GET | `/health` | Health (perm. `audit.view`) |
| POST | `/bot/simulate` | Simular cliente |

Lista estendida: [`docs/API.md`](docs/API.md)

---

## 16. Banco de dados

Principais tabelas:

- `clients`, `conversations`, `messages`, `attachments`
- `client_phone_identities`, `conversation_active_cycles`, `webhook_receipts`
- `bot_topics`, `bot_knowledge`
- `users`, `roles`, `permissions`, `departments`
- `settings`, `audit_logs`
- `conversation_transfers`, `conversation_internal_notes`
- `message_status_events`, `scheduled_messages`, `failed_jobs`, `jobs`

Colunas úteis: `closed_by`, `waiting_since`, `sla_due_at`, `delivered_at`, `read_at`, `assigned_to`.

---

## 17. Papéis e permissões

Seed: `RolePermissionSeeder`

| Papel | Escopo típico |
|-------|----------------|
| `super-admin` | Tudo |
| `administrador` | Equipe, settings, bot, audit, conversas |
| `supervisor` | Conversas, transfers, reports, audit |
| `atendente` | Inbox, notas, clientes |

Middleware: `permission:slug` (aceita vários, separados por vírgula).

Rotas web sensíveis exigem a permissão correspondente (ex.: `/settings` → `settings.manage`).

---

## 18. Performance e robustez

| Recurso | Detalhe |
|---------|---------|
| Settings / flags | Cache ~10 min + invalidação |
| Inbox | Patch WebSocket + debounce; SQL com escopo de departamento |
| Mensagens | Paginação 50 + `before_id` |
| Webhook | Fast-ack 202 + job |
| Presença | Heartbeat 60s; offline no `pagehide` / logout; requeue ~150s |
| Jobs | Retries, backoff, `WithoutOverlapping` por conversa |
| SLA | Minutos úteis (`BusinessHoursService`) |

Feature flags (`settings.features`): `realtime`, `internal_notes`, `transfers`, `audit_log`, `business_hours_bot`, `message_status_webhooks`, `bot_panel_simulator`.

Detalhes: [`docs/ROBUSTNESS.md`](docs/ROBUSTNESS.md)

---

## 19. Operações, filas e health

Tela **Operações** (`/operations`, perm. `audit.view`):

- Checks: DB, storage, cache, Redis, scheduler, fila, Reverb, Meta
- Contadores: pendentes / reservados / failed_jobs
- Reenvio de mensagens falhas (quando seguro)
- Browser de **audit log**

Comandos do scheduler (`routes/console.php`):

```bash
messages:dispatch-scheduled
agents:requeue-offline
operations:heartbeat
operations:prune
```

Em produção com Redis:

```env
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

Horizon é opcional (`composer require laravel/horizon`) quando o ambiente Composer permitir; o painel embutido já cobre o essencial.

---

## 20. Testes

```bash
php artisan config:clear
php artisan test
```

Suites principais:

| Arquivo | Cobertura |
|---------|-----------|
| `ConversationRobustnessTest` | Ciclo conversa, bot, mídia, steal |
| `MessagingReliabilityTest` | Idempotência, status, webhook, retry |
| `PresenceAuthorizationTest` | Offline → fila, auth |
| `InboxOpsSecurityTest` | Paginação inbox, login throttle, 24h, perms |

Simular cliente sem WhatsApp:

```bash
php artisan whatsapp:simulate
# ou POST /api/internal/bot/simulate
```

---

## 21. CI / GitHub Actions

Workflow: [`.github/workflows/tests.yml`](.github/workflows/tests.yml)

- Triggers: push `main` / `master` / `*.x`, PRs, cron diário
- Matrix PHP **8.2 / 8.3 / 8.4**
- `composer install` → `php artisan test`
- `npm install` → `npm run build`

---

## 22. Deploy / produção

Checklist:

1. `APP_ENV=production`, `APP_DEBUG=false`, HTTPS em `APP_URL`
2. `WHATSAPP_DRIVER=meta` + token + **App Secret** + webhook verificado
3. Redis para session/queue/cache
4. Supervisor/systemd: `queue:work`, `schedule:work`, `reverb:start` (se Echo)
5. `php artisan storage:link` + `migrate --force`
6. Backup MySQL
7. Não commitar `.env`, `cacert.pem`, tokens

Mais: [`docs/DEPLOY.md`](docs/DEPLOY.md)

---

## 23. Estrutura de pastas

```
├── app/
│   ├── Application/     Services, DTOs, Contracts
│   ├── Domain/          Enums
│   ├── Infrastructure/  Eloquent, WhatsApp, Audit
│   ├── Http/            Controllers, Requests, Middleware
│   └── Jobs/ Events/
├── config/              bot, whatsapp, chatflow, …
├── database/            migrations, seeders, factories
├── docs/                documentação estendida
├── resources/views      painel Blade
├── resources/js         Alpine (chat, bot-knowledge, echo)
├── routes/              web, api, channels, console
├── tests/Feature        robustez, messaging, inbox/ops
├── docker-compose.yml
├── Dockerfile
└── public/              entrypoint
```

---

## 24. Documentação adicional

| Arquivo | Conteúdo |
|---------|----------|
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Camadas, providers, jobs |
| [`docs/BOT.md`](docs/BOT.md) | Fluxo e configuração do robô |
| [`docs/WHATSAPP.md`](docs/WHATSAPP.md) | Meta, webhook, mídia |
| [`docs/API.md`](docs/API.md) | Endpoints internos |
| [`docs/DEPLOY.md`](docs/DEPLOY.md) | Produção |
| [`docs/ROBUSTNESS.md`](docs/ROBUSTNESS.md) | Filas, presença, SLA, Redis, Docker |

---

## 25. Credenciais padrão

Após `php artisan migrate --seed`:

| Campo | Valor |
|-------|--------|
| E-mail | `admin@chatflow.com` |
| Senha | `password` |

**Altere imediatamente em produção.**

phpMyAdmin (Docker): `http://localhost:8081` — user `chatflow` / senha `secret` (Compose). Em XAMPP/Laragon local: `http://localhost/phpmyadmin` (root / vazio conforme `.env`).

---

## 26. Roadmap

- [x] Inbox + bot FAQ + encerradas
- [x] Fotos (receber/enviar)
- [x] Status webhook, transfer, notes, audit, flags, SLA
- [x] Idempotência, retries, presença e retorno à fila
- [x] Assinatura Meta, health, mensagens com falha
- [x] UI de notas, transferência, assumir e agendar
- [x] Templates Meta + janela 24h
- [x] Docker Compose + Redis + painel de filas / audit
- [ ] Reverb ligado por padrão no seed local
- [ ] Laravel Horizon (quando Composer/SSL permitir)
- [ ] Multi-tenant / múltiplos números WhatsApp

---

## 27. Licença

Projeto proprietário — MGI Chat / Pedro Christovam.  
Base Laravel (MIT) — ver histórico do framework.

---

## 28. Suporte

- Issues e código: [Pedrochristovam/chatboot](https://github.com/Pedrochristovam/chatboot)
- Em caso de dúvida de operação: comece por [`docs/ROBUSTNESS.md`](docs/ROBUSTNESS.md) e a tela **Operações**

---

**Autor / repositório:** [Pedrochristovam/chatboot](https://github.com/Pedrochristovam/chatboot)
