<?php

namespace Application\Services\Conversation;

use App\Events\ConversationUpdated;
use Application\Services\Settings\FeatureFlagService;
use Domain\Shared\Enums\ConversationOrigin;
use Domain\Shared\Enums\ConversationStatus;
use Illuminate\Support\Facades\DB;
use Infrastructure\Logging\AuditLogger;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\User;

class ConversationService
{
    public function __construct(
        private readonly ConversationTransferService $transferService,
        private readonly ConversationWorkflowService $workflow,
        private readonly SlaService $sla,
        private readonly FeatureFlagService $features,
        private readonly AuditLogger $audit,
    ) {}

    public function listForInbox(array $filters = [], ?User $viewer = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Conversation::query()
            ->with(['client', 'assignedAgent', 'closedByAgent', 'tags', 'messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->orderByRaw('CASE WHEN status = ? AND sla_due_at IS NOT NULL THEN 0 ELSE 1 END', [ConversationStatus::Waiting->value])
            ->orderBy('sla_due_at')
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('client', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('phone_normalized', 'like', '%'.preg_replace('/\D+/', '', $search).'%');
            });
        }

        $statusFilter = $filters['status'] ?? 'all';

        if ($statusFilter === 'bot') {
            $query->whereIn('status', [ConversationStatus::BotActive, ConversationStatus::BotClosed]);
        } elseif ($statusFilter === 'bot_closed') {
            $query->where('status', ConversationStatus::BotClosed);
        } elseif ($statusFilter === 'all') {
            $query->whereIn('status', [
                ConversationStatus::Waiting,
                ConversationStatus::InProgress,
            ]);
        } elseif ($statusFilter === 'waiting') {
            $query->where('status', ConversationStatus::Waiting);
        } elseif ($statusFilter === 'active') {
            $query->where('status', ConversationStatus::InProgress);
        } elseif ($statusFilter === 'closed') {
            $query->where('status', ConversationStatus::Closed);

            if (! empty($filters['closed_by'])) {
                $query->where('closed_by', (int) $filters['closed_by']);
            } elseif (! empty($filters['assigned_to'])) {
                $query->where(function ($q) use ($filters) {
                    $agentId = (int) $filters['assigned_to'];
                    $q->where('closed_by', $agentId)
                        ->orWhere(function ($q2) use ($agentId) {
                            $q2->whereNull('closed_by')->where('assigned_to', $agentId);
                        });
                });
            }
        }

        if ($viewer) {
            $this->scopeForViewer($query, $viewer);
        }

        return $query->limit(50)->get();
    }

    public function findWithDetails(int $id): ?Conversation
    {
        return Conversation::query()
            ->with(['client.tags', 'assignedAgent', 'closedByAgent', 'department', 'tags'])
            ->find($id);
    }

    public function inboxCard(int $conversationId, ?User $viewer = null): ?array
    {
        $query = Conversation::query()
            ->with(['client', 'assignedAgent', 'closedByAgent', 'tags', 'messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->whereKey($conversationId);

        if ($viewer) {
            $this->scopeForViewer($query, $viewer);
        }

        $conversation = $query->first();

        return $conversation ? $this->toInboxItem($conversation) : null;
    }

    private function scopeForViewer($query, User $viewer): void
    {
        if ($viewer->hasRole('super-admin')
            || $viewer->hasRole('administrador')
            || $viewer->hasRole('supervisor')) {
            return;
        }

        $departmentIds = $viewer->departmentIds();

        $query->where(function ($q) use ($viewer, $departmentIds) {
            $q->where('assigned_to', $viewer->id)
                ->orWhere(function ($waiting) {
                    $waiting->where('status', ConversationStatus::Waiting)
                        ->whereNull('assigned_to');
                });

            if ($departmentIds !== []) {
                $q->orWhereIn('department_id', $departmentIds);
            }
        });
    }

    public function assign(Conversation $conversation, ?int $agentId, ?int $byUserId = null): Conversation
    {
        $id = DB::transaction(function () use ($conversation, $agentId, $byUserId) {
            $locked = $this->workflow->lock($conversation);
            $from = $locked->assigned_to;
            if ($from !== null && $agentId !== null && (int) $from !== (int) $agentId) {
                $actor = $byUserId ? User::query()->find($byUserId) : null;
                $canOverride = $actor?->roles()
                    ->whereIn('slug', ['super-admin', 'administrador', 'supervisor'])
                    ->exists() === true;
                if (! $canOverride) {
                    throw new \RuntimeException('Esta conversa já foi assumida por outro atendente.');
                }
            }
            $targetStatus = $agentId === null
                ? ConversationStatus::Waiting
                : ConversationStatus::InProgress;

            $this->workflow->transitionLocked($locked, $targetStatus, [
                'assigned_to' => $agentId,
                'is_bot_handled' => false,
                'waiting_since' => $agentId === null ? now() : null,
                'sla_due_at' => $agentId === null ? $this->sla->dueAt() : null,
            ]);

            if ($byUserId && $this->features->isEnabled('transfers', true)) {
                $this->transferService->logAssignment($locked, $from, $agentId, $byUserId);
            }

            if ($this->features->isEnabled('audit_log', true)) {
                $this->audit->log('conversation.assigned', $locked, ['assigned_to' => $from], ['assigned_to' => $agentId]);
            }
            DB::afterCommit(fn () => event(new ConversationUpdated($locked->id, 'assigned')));

            return $locked->id;
        }, 3);

        return Conversation::query()->with(['client', 'assignedAgent'])->findOrFail($id);
    }

    public function close(Conversation $conversation, ?int $closedByAgentId = null): Conversation
    {
        $id = DB::transaction(function () use ($conversation, $closedByAgentId) {
            $locked = $this->workflow->lock($conversation);
            $agentId = $closedByAgentId ?? $locked->assigned_to;

            $this->workflow->transitionLocked($locked, ConversationStatus::Closed, [
                'closed_at' => now(),
                'resolved_at' => now(),
                'closed_by' => $agentId,
                'assigned_to' => $locked->assigned_to ?? $agentId,
                'is_bot_handled' => false,
                'unread_count' => 0,
                'waiting_since' => null,
                'sla_due_at' => null,
            ]);

            if ($this->features->isEnabled('audit_log', true)) {
                $this->audit->log('conversation.closed', $locked, null, ['closed_by' => $agentId]);
            }

            return $locked->id;
        }, 3);

        return Conversation::query()
            ->with(['client', 'assignedAgent', 'closedByAgent'])
            ->findOrFail($id);
    }

    public function findOrCreateForClient(Client $client, bool $botEnabled = true): Conversation
    {
        return DB::transaction(function () use ($client, $botEnabled) {
            $normalizedPhone = $this->normalizedPhone($client);
            $now = now();

            DB::table('client_phone_identities')->insertOrIgnore([
                'normalized_phone' => $normalizedPhone,
                'canonical_client_id' => $client->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $identity = DB::table('client_phone_identities')
                ->where('normalized_phone', $normalizedPhone)
                ->lockForUpdate()
                ->first();

            if (! $identity) {
                throw new \RuntimeException('Não foi possível consolidar a identidade telefônica do cliente.');
            }

            $cycle = DB::table('conversation_active_cycles')
                ->where('normalized_phone', $normalizedPhone)
                ->lockForUpdate()
                ->first();

            if ($cycle) {
                $active = Conversation::query()->lockForUpdate()->find($cycle->conversation_id);
                if ($active && $this->workflow->isActive($active->status)) {
                    return $active;
                }

                DB::table('conversation_active_cycles')
                    ->where('normalized_phone', $normalizedPhone)
                    ->delete();
            }

            $clientIds = Client::query()
                ->withTrashed()
                ->where('phone_normalized', $normalizedPhone)
                ->pluck('id')
                ->push($client->id)
                ->unique()
                ->all();

            $existing = Conversation::query()
                ->whereIn('client_id', $clientIds)
                ->whereIn('status', [
                    ConversationStatus::BotActive,
                    ConversationStatus::Waiting,
                    ConversationStatus::InProgress,
                ])
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $this->registerActiveCycle($normalizedPhone, $existing->id);

                return $existing;
            }

            $conversation = Conversation::query()->create([
                'client_id' => $identity->canonical_client_id,
                'status' => $botEnabled ? ConversationStatus::BotActive : ConversationStatus::Waiting,
                'is_bot_handled' => $botEnabled,
                'origin' => ConversationOrigin::Whatsapp,
                'last_message_at' => $now,
                'waiting_since' => $botEnabled ? null : $now,
                'sla_due_at' => $botEnabled ? null : $this->sla->dueAt($now),
            ]);

            $this->registerActiveCycle($normalizedPhone, $conversation->id);

            return $conversation;
        }, 3);
    }

    private function normalizedPhone(Client $client): string
    {
        $normalized = preg_replace(
            '/\D+/',
            '',
            (string) ($client->phone_normalized ?: $client->phone),
        ) ?: '';

        if ($normalized === '') {
            throw new \InvalidArgumentException('O cliente precisa ter um telefone normalizável.');
        }

        return $normalized;
    }

    private function registerActiveCycle(string $normalizedPhone, int $conversationId): void
    {
        DB::table('conversation_active_cycles')->updateOrInsert(
            ['normalized_phone' => $normalizedPhone],
            [
                'conversation_id' => $conversationId,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function toInboxArray(\Illuminate\Database\Eloquent\Collection $conversations): array
    {
        return $conversations->map(fn (Conversation $c) => $this->toInboxItem($c))->values()->all();
    }

    public function toInboxItem(Conversation $c): array
    {
        $lastMessage = $c->relationLoaded('messages')
            ? $c->messages->first()
            : $c->messages()->latest('id')->first();
        $primaryTag = $c->tags->first();

        return [
            'id' => $c->id,
            'name' => $c->client->name,
            'initials' => strtoupper(substr($c->client->name, 0, 2)),
            'phone' => $c->client->phone,
            'preview' => $lastMessage?->content ?? 'Nova conversa',
            'time' => $c->last_message_at?->format('H:i') ?? $c->created_at->format('H:i'),
            'unread' => $c->unread_count,
            'online' => true,
            'tag' => $primaryTag?->name,
            'tagClass' => match ($primaryTag?->slug) {
                'urgente' => 'bg-red-100 text-red-700',
                'vip' => 'bg-emerald-100 text-emerald-700',
                default => 'bg-blue-100 text-blue-700',
            },
            'status' => match ($c->status) {
                ConversationStatus::BotActive, ConversationStatus::BotClosed => 'bot',
                ConversationStatus::Waiting => 'waiting',
                ConversationStatus::InProgress => 'active',
                default => 'closed',
            },
            'conversation_status' => $c->status->value,
            'status_label' => $c->status->label(),
            'is_read_only' => $c->status->isReadOnlyForAgents(),
            'is_bot' => $c->status->isBot(),
            'bot_closed_at' => $c->bot_closed_at?->format('d/m/Y H:i'),
            'closed_at' => $c->closed_at?->format('d/m/Y H:i'),
            'closed_by' => $c->closedByAgent?->name ?? $c->assignedAgent?->name,
            'assigned_agent' => $c->assignedAgent?->name,
            'client_id' => $c->client_id,
            'waiting_since' => $c->waiting_since?->toIso8601String(),
            'sla_due_at' => $c->sla_due_at?->toIso8601String(),
            'sla_state' => $this->sla->state($c),
        ];
    }
}
