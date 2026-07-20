<?php

namespace Application\Services\Conversation;

use App\Events\ConversationUpdated;
use Domain\Shared\Enums\AgentStatus;
use Domain\Shared\Enums\ConversationStatus;
use Illuminate\Support\Facades\DB;
use Infrastructure\Logging\AuditLogger;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\User;

class AgentPresenceService
{
    public function __construct(
        private readonly SlaService $sla,
        private readonly AuditLogger $audit,
    ) {}

    public function heartbeat(User $user): void
    {
        // Evita write a cada request quando o sinal ainda está fresco.
        if ($user->status === AgentStatus::Online
            && $user->last_seen_at
            && $user->last_seen_at->greaterThan(now()->subSeconds(45))) {
            return;
        }

        $user->forceFill([
            'status' => AgentStatus::Online,
            'last_seen_at' => now(),
        ])->save();
    }

    public function markOffline(User $user, string $reason = 'offline'): int
    {
        $conversationIds = DB::transaction(function () use ($user, $reason) {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);
            if (! $lockedUser) {
                return [];
            }

            $lockedUser->forceFill([
                'status' => AgentStatus::Offline,
                'last_seen_at' => now(),
            ])->save();

            $conversations = Conversation::query()
                ->where('assigned_to', $lockedUser->id)
                ->where('status', ConversationStatus::InProgress)
                ->lockForUpdate()
                ->get();

            foreach ($conversations as $conversation) {
                $old = [
                    'assigned_to' => $conversation->assigned_to,
                    'status' => $conversation->status->value,
                ];
                $conversation->update([
                    'assigned_to' => null,
                    'status' => ConversationStatus::Waiting,
                    'is_bot_handled' => false,
                ]);
                $this->sla->startWaiting($conversation);
                $this->audit->log('conversation.returned_to_queue', $conversation, $old, [
                    'assigned_to' => null,
                    'status' => ConversationStatus::Waiting->value,
                    'reason' => $reason,
                ]);
            }

            return $conversations->modelKeys();
        });

        foreach ($conversationIds as $id) {
            event(new ConversationUpdated($id, 'returned_to_queue'));
        }

        return count($conversationIds);
    }

    public function requeueStaleAgents(int $staleAfterSeconds = 150): int
    {
        $agents = User::query()
            ->where('status', AgentStatus::Online)
            ->where(function ($query) use ($staleAfterSeconds) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', now()->subSeconds($staleAfterSeconds));
            })
            ->get();

        $count = 0;
        foreach ($agents as $agent) {
            $count += $this->markOffline($agent, 'heartbeat_timeout');
        }

        return $count;
    }
}
