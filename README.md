# MGI chat — WhatsApp CRM + Robô de Atendimento

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/license-Proprietary-lightgrey)

**MGI chat** (também chamado ChatFlow CRM) é um sistema de atendimento via **WhatsApp** com:

- Inbox de conversas para a equipe humana  
- Robô (FAQ + menu de assuntos)  
- Painel administrativo (clientes, atendentes, relatórios, configurações)  
- Envio/recebimento de **fotos**  
- Base preparada para status de entrega, transferências, notas internas, auditoria e tempo real  

Repositório: [github.com/Pedrochristovam/chatboot](https://github.com/Pedrochristovam/chatboot)

---

## Índice

1. [Visão geral](#1-visão-geral)  
2. [Stack técnica](#2-stack-técnica)  
3. [Arquitetura](#3-arquitetura)  
4. [Funcionalidades](#4-funcionalidades)  
5. [Pré-requisitos](#5-pré-requisitos)  
6. [Instalação local](#6-instalação-local)  
7. [Variáveis de ambiente](#7-variáveis-de-ambiente)  
8. [Como rodar no dia a dia](#8-como-rodar-no-dia-a-dia)  
9. [Fluxo do robô](#9-fluxo-do-robô)  
10. [WhatsApp / Meta Cloud API](#10-whatsapp--meta-cloud-api)  
11. [Mídia (fotos)](#11-mídia-fotos)  
12. [APIs internas](#12-apis-internas)  
13. [Banco de dados](#13-banco-de-dados)  
14. [Papéis e permissões](#14-papéis-e-permissões)  
15. [Feature flags e robustez](#15-feature-flags-e-robustez)  
16. [Testes sem número WhatsApp](#16-testes-sem-número-whatsapp)  
17. [Deploy / produção](#17-deploy--produção)  
18. [Estrutura de pastas](#18-estrutura-de-pastas)  
19. [Documentação adicional](#19-documentação-adicional)  
20. [Credenciais padrão](#20-credenciais-padrão)  
21. [Roadmap](#21-roadmap)  
22. [Licença](#22-licença)  

---

## 1. Visão geral

O cliente fala no WhatsApp → o webhook chega no Laravel → a mensagem é processada (fila) → o **robô** responde ou a conversa vai para a **fila humana** → o atendente responde pelo painel → o job envia de volta pelo provedor WhatsApp.

```
WhatsApp ──webhook──► Laravel API ──queue──► MessageService / BotService
                                              │
                                              ├── salva messages + attachments
                                              ├── responde (bot) ou waiting (humano)
                                              └── SendWhatsAppMessageJob ──► Meta / stub
```

Marca visual: bordo sólido `#8B1E3F`. Fuso: **America/Sao_Paulo**.

---

## 2. Stack técnica

| Camada | Tecnologia |
|--------|------------|
| Backend | PHP 8.2+, Laravel 12 |
| Banco | MySQL (`chatflow_crm`) |
| Front | Blade + Alpine.js + Tailwind CSS 4 + Vite |
| Filas | Database queue (`php artisan queue:work`) |
| Tempo real (opcional) | Laravel Reverb + Echo |
| WhatsApp | Meta Cloud API (`WHATSAPP_DRIVER=meta`) ou stub local (`null`) |
| Auth | Sessão web (Sanctum disponível) |

---

## 3. Arquitetura

O código de domínio fica em camadas sob `app/`:

```
app/
├── Application/          # Casos de uso (Services, DTOs, Contracts)
├── Domain/               # Enums e regras de domínio
├── Infrastructure/       # Eloquent, WhatsApp providers, AuditLogger
├── Http/Controllers/     # Web + Api
├── Jobs/                 # ProcessIncoming, SendWhatsApp, Status
└── Events/               # MessageReceived / MessageSent (broadcast)
```

**Provedores WhatsApp** (`Infrastructure/WhatsApp`):

- `NullWhatsAppProvider` — simula envio (dev)  
- `MetaCloudProvider` — Cloud API (produção)  
- Stubs: Evolution, Z-API, Baileys  

Documentação detalhada: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)

---

## 4. Funcionalidades

### Painel
- **Dashboard** — KPIs do dia  
- **Conversas** — inbox ativa (waiting / em atendimento)  
- **Encerradas por mim** — arquivo por atendente  
- **Encerradas pelo robô** — auditoria do bot  
- **Robô & FAQ** — mensagem de nome, menu, FAQs, saudação de retorno  
- **Clientes / Atendentes / Relatórios / Configurações**  

### Robô
1. Cumprimento → pede nome (ou “que bom ter você de volta”)  
2. Menu de assuntos (`bot_topics`)  
3. Respostas por palavras-chave (`bot_knowledge`)  
4. Transferência para humano (`atendente` / assunto “Outros”)  
5. Encerramento por palavras de agradecimento  

### Humanos
- Responder texto e **imagem**  
- Encerrar (sai da inbox → “Encerradas por mim”)  
- Base pronta: transferir, notas internas, status delivered/read  

---

## 5. Pré-requisitos

- PHP 8.2+ com extensões: `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`  
- Composer 2  
- Node.js 20+ e npm  
- MySQL 8  
- (Opcional) ngrok/cloudflare tunnel para webhook Meta em local  

---

## 6. Instalação local

```bash
git clone https://github.com/Pedrochristovam/chatboot.git
cd chatboot

composer install
cp .env.example .env
php artisan key:generate

# Ajuste DB_* no .env (banco chatflow_crm)
php artisan migrate --seed

npm install
npm run build
# ou, em desenvolvimento:
npm run dev

php artisan storage:link
```

Crie o banco MySQL antes:

```sql
CREATE DATABASE chatflow_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 7. Variáveis de ambiente

Principais chaves (veja `.env.example` completo):

| Variável | Descrição |
|----------|-----------|
| `APP_URL` | URL pública (ex.: `http://127.0.0.1:8888`) |
| `APP_TIMEZONE` | `America/Sao_Paulo` |
| `DB_*` | Conexão MySQL |
| `QUEUE_CONNECTION` | `database` |
| `WHATSAPP_DRIVER` | `null` (local) ou `meta` |
| `WHATSAPP_META_TOKEN` | Token Cloud API |
| `WHATSAPP_META_PHONE_NUMBER_ID` | Phone Number ID |
| `WHATSAPP_WEBHOOK_VERIFY_TOKEN` | Token de verificação do webhook |
| `BOT_ENABLED` | Liga/desliga robô |
| `BROADCAST_CONNECTION` | `log` ou `reverb` |
| `REVERB_*` / `VITE_REVERB_*` | Tempo real |

**Nunca versione o arquivo `.env`.**

---

## 8. Como rodar no dia a dia

Em **3 terminais** (mínimo):

```bash
# 1) App
php -S 127.0.0.1:8888 -t public

# 2) Fila (obrigatória para envio WhatsApp / jobs)
php artisan queue:work

# 3) Front (só se estiver mexendo em JS/CSS)
npm run dev
```

Acesse: `http://127.0.0.1:8888`

---

## 9. Fluxo do robô

Configuração visual: **Robô & FAQ** (`/bot-knowledge`).

| Parte | O que faz |
|-------|-----------|
| **A** | Mensagem pedindo nome + mensagem “de volta” (`{name}`) |
| **B** | Assuntos do menu (ordem, ativo, só-humano) |
| **C** | FAQs: pergunta, resposta, **palavras-chave** |

Comandos do cliente: `menu`, `atendente`, palavras de encerramento (`obrigado`, `tchau`…).

Fora do horário comercial (feature `business_hours_bot`): mensagem automática + fila humana.

Detalhes: [`docs/BOT.md`](docs/BOT.md)

---

## 10. WhatsApp / Meta Cloud API

### Modo desenvolvimento
```env
WHATSAPP_DRIVER=null
```
Envios “fingem” sucesso; use o simulador.

### Modo Meta
```env
WHATSAPP_DRIVER=meta
WHATSAPP_META_TOKEN=...
WHATSAPP_META_PHONE_NUMBER_ID=...
WHATSAPP_WEBHOOK_VERIFY_TOKEN=chatflow_webhook_secret
```

Webhook:

- Verify: `GET  {APP_URL}/api/webhook/whatsapp`  
- Receive: `POST {APP_URL}/api/webhook/whatsapp`  

O sistema já processa:

- mensagens de texto/mídia  
- **statuses** (`sent`, `delivered`, `read`, `failed`)  

Guia: [`docs/WHATSAPP.md`](docs/WHATSAPP.md)

---

## 11. Mídia (fotos)

- Cliente envia imagem → Meta media id → download → `storage/app/public/whatsapp/...` → anexo na mensagem → preview no painel  
- Atendente anexa imagem no composer → upload local → job envia (upload Meta ou stub)  

Limite UI: **5 MB** (jpeg/png/webp/gif).

---

## 12. APIs internas

Prefixo autenticado (sessão web): `/api/internal/...`

Exemplos:

| Método | Rota | Uso |
|--------|------|-----|
| GET | `/conversations` | Inbox |
| POST | `/conversations/{id}/messages` | Texto ou `multipart` com `image` |
| POST | `/conversations/{id}/close` | Encerrar |
| POST | `/conversations/{id}/transfer` | Transferir (base pronta) |
| GET/POST | `/conversations/{id}/notes` | Notas internas |
| POST | `/bot/simulate` | Simular cliente |
| GET/PUT | `/bot-knowledge` | FAQ / assuntos |

Lista completa: [`docs/API.md`](docs/API.md)

---

## 13. Banco de dados

Principais tabelas:

- `clients`, `conversations`, `messages`, `attachments`  
- `bot_topics`, `bot_knowledge`  
- `users`, `roles`, `permissions`  
- `settings`, `audit_logs`  
- `conversation_transfers`, `conversation_internal_notes`  
- `message_status_events`, `scheduled_messages`  

Colunas úteis de robustez: `closed_by`, `waiting_since`, `sla_due_at`, `delivered_at`, `read_at`.

---

## 14. Papéis e permissões

Seed: `RolePermissionSeeder`

| Papel | Escopo típico |
|-------|----------------|
| `super-admin` | Tudo |
| `administrador` | Equipe, settings, bot, audit |
| `supervisor` | Conversas, transfers, reports |
| `atendente` | Inbox, notas, clientes |

Middleware alias: `permission` (pronto para aplicar nas rotas).

---

## 15. Feature flags e robustez

Grupo `settings.features` (seed):

- `realtime`  
- `internal_notes`  
- `transfers`  
- `audit_log`  
- `business_hours_bot`  
- `message_status_webhooks`  
- `bot_panel_simulator`  

Serviços: `FeatureFlagService`, `BusinessHoursService`, `MessageStatusService`, `ConversationTransferService`, `InternalNoteService`, `AuditLogger`.

---

## 16. Testes sem número WhatsApp

```bash
php artisan whatsapp:simulate
# ou
# POST /api/internal/bot/simulate  { "phone": "5511999990001", "content": "oi", "name": "Maria" }
```

Comandos do simulador: `/historico`, `/reset`, `/sair`.

---

## 17. Deploy / produção

Checklist resumido:

1. `APP_ENV=production`, `APP_DEBUG=false`, HTTPS em `APP_URL`  
2. `WHATSAPP_DRIVER=meta` + token + webhook verificado  
3. Supervisor/systemd em `queue:work` (e `reverb:start` se usar Echo)  
4. `php artisan storage:link`  
5. Backup MySQL  
6. Não commitar `.env`, tokens, `cacert.pem` com dados sensíveis  

Mais: [`docs/DEPLOY.md`](docs/DEPLOY.md)

---

## 18. Estrutura de pastas

```
├── app/Application|Domain|Infrastructure|Http|Jobs|Events
├── config/          bot.php, whatsapp.php, chatflow.php, ai.php
├── database/migrations|seeders
├── docs/            documentação estendida
├── resources/views  painel Blade
├── resources/js     Alpine, chat, bot-knowledge, echo
├── routes/web.php|api.php|channels.php
└── public/          entrypoint
```

---

## 19. Documentação adicional

| Arquivo | Conteúdo |
|---------|----------|
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Camadas, providers, jobs |
| [`docs/BOT.md`](docs/BOT.md) | Fluxo e configuração do robô |
| [`docs/WHATSAPP.md`](docs/WHATSAPP.md) | Meta, webhook, mídia |
| [`docs/API.md`](docs/API.md) | Endpoints internos |
| [`docs/DEPLOY.md`](docs/DEPLOY.md) | Produção e operação |

---

## 20. Credenciais padrão

Após `db:seed`:

| Campo | Valor |
|-------|--------|
| E-mail | `admin@chatflow.com` |
| Senha | `password` |

**Altere imediatamente em produção.**

---

## 21. Roadmap

- [x] Inbox + bot FAQ + encerradas  
- [x] Fotos (receber/enviar)  
- [x] Base: status webhook, transfer, notes, audit, flags, SLA  
- [ ] UI de notas e transferência  
- [ ] Reverb ligado por padrão  
- [ ] Templates oficiais Meta (janela 24h)  
- [ ] Horizon / monitoramento de fila  

---

## 22. Licença

Projeto proprietário — MGI chat / Pedro Christovam.  
Base inicial Laravel (MIT) — ver histórico do framework.

---

**Autor / repositório:** [Pedrochristovam/chatboot](https://github.com/Pedrochristovam/chatboot)
