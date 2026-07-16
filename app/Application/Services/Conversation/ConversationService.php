<?php

namespace Application\Services\Conversation;

use Application\Services\Settings\FeatureFlagService;
use Domain\Shared\Enums\ConversationOrigin;
use Domain\Shared\Enums\ConversationStatus;
use Infrastructure\Logging\AuditLogger;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;

class ConversationService
{
    public function __construct(
        private readonly ConversationTransferService $transferService,
        private readonly FeatureFlagService $features,
        private readonly AuditLogger $audit,
    ) {}

    public function listForInbox(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Conversation::query()
            ->with(['client', 'assignedAgent', 'closedByAgent', 'tags', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('client', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $statusFilter = $filters['status'] ?? 'all';

        if ($statusFilter === 'bot') {
            $query->whereIn('status', [ConversationStatus::BotActive, ConversationStatus::BotClosed]);
        } elseif ($statusFilter === 'bot_closed') {
            $query->where('status', ConversationStatus::BotClosed);
        } elseif ($statusFilter === 'all') {
            // Inbox ativa: sem bot e sem encerradas (humano/resolvidas)
            $query->whereNotIn('status', [
                ConversationStatus::BotActive,
                ConversationStatus::BotClosed,
                ConversationStatus::Closed,
                ConversationStatus::Resolved,
            ]);
        } elseif ($statusFilter === 'waiting') {
            $query->where('status', ConversationStatus::Waiting);
        } elseif ($statusFilter === 'active') {
            $query->where('status', ConversationStatus::InProgress);
        } elseif ($statusFilter === 'closed') {
            $query->where('status', ConversationStatus::Closed);

            // Cada atendente vê só as que ele encerrou
            if (! empty($filters['closed_by'])) {
                $query->where('closed_by', (int) $filters['closed_by']);
            } elseif (! empty($filters['assigned_to'])) {
                // fallback legado
                $query->where(function ($q) use ($filters) {
                    $agentId = (int) $filters['assigned_to'];
                    $q->where('closed_by', $agentId)
                        ->orWhere(function ($q2) use ($agentId) {
                            $q2->whereNull('closed_by')->where('assigned_to', $agentId);
                        });
                });
            }
        }

        return $query->limit(50)->get();
    }

    public function findWithDetails(int $id): ?Conversation
    {
        return Conversation::query()
            ->with(['client.tags', 'assignedAgent', 'closedByAgent', 'department', 'tags', 'messages.attachments'])
            ->find($id);
    }

    public function assign(Conversation $conversation, ?int $agentId, ?int $byUserId = null): Conversation
    {
        $from = $conversation->assigned_to;

        $conversation->update([
            'assigned_to' => $agentId,
            'status' => ConversationStatus::InProgress,
            'is_bot_handled' => false,
            'waiting_since' => null,
        ]);

        if ($byUserId && $this->features->isEnabled('transfers', true)) {
            $this->transferService->logAssignment($conversation, $from, $agentId, $byUserId);
        }

        if ($this->features->isEnabled('audit_log', true)) {
            $this->audit->log('conversation.assigned', $conversation, ['assigned_to' => $from], ['assigned_to' => $agentId]);
        }

        return $conversation->fresh(['client', 'assignedAgent']);
    }

    public function close(Conversation $conversation, ?int $closedByAgentId = null): Conversation
    {
        $agentId = $closedByAgentId ?? $conversation->assigned_to;

        $conversation->update([
            'status' => ConversationStatus::Closed,
            'closed_at' => now(),
            'resolved_at' => now(),
            'closed_by' => $agentId,
            'assigned_to' => $conversation->assigned_to ?? $agentId,
            'is_bot_handled' => false,
            'unread_count' => 0,
            'waiting_since' => null,
            'sla_due_at' => null,
        ]);

        if ($this->features->isEnabled('audit_log', true)) {
            $this->audit->log('conversation.closed', $conversation, null, ['closed_by' => $agentId]);
        }

        return $conversation->fresh(['client', 'assignedAgent', 'closedByAgent']);
    }

    public function findOrCreateForClient(Client $client, bool $botEnabled = true): Conversation
    {
        $humanActive = Conversation::query()
            ->where('client_id', $client->id)
            ->whereIn('status', [ConversationStatus::Waiting, ConversationStatus::InProgress])
            ->first();

        if ($humanActive) {
            return $humanActive;
        }

        $botActive = Conversation::query()
            ->where('client_id', $client->id)
            ->where('status', ConversationStatus::BotActive)
            ->first();

        if ($botActive) {
            return $botActive;
        }

        return Conversation::query()->create([
            'client_id' => $client->id,
            'status' => $botEnabled ? ConversationStatus::BotActive : ConversationStatus::Waiting,
            'is_bot_handled' => $botEnabled,
            'origin' => ConversationOrigin::Whatsapp,
            'last_message_at' => now(),
            'waiting_since' => $botEnabled ? null : now(),
            'sla_due_at' => $botEnabled ? null : now()->addMinutes(15),
        ]);
    }

    public function toInboxArray(\Illuminate\Database\Eloquent\Collection $conversations): array
    {
        return $conversations->map(function (Conversation $c) {
            $lastMessage = $c->messages->first();
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
            ];
        })->values()->all();
    }
}
