<?php

namespace Application\Services\Conversation;

use App\Events\ConversationUpdated;
use Domain\Conversation\Events\ConversationStatusChanged;
use Domain\Conversation\Exceptions\InvalidConversationTransition;
use Domain\Shared\Enums\ConversationStatus;
use Domain\Shared\Enums\MessageSenderType;
use Domain\Shared\Enums\MessageStatus;
use Illuminate\Support\Facades\DB;
use Infrastructure\Persistence\Eloquent\Models\Conversation;

class ConversationWorkflowService
{
    /** @var array<string, list<ConversationStatus>> */
    private const ALLOWED_TRANSITIONS = [
        'bot_active' => [
            ConversationStatus::BotActive,
            ConversationStatus::Waiting,
            ConversationStatus::BotClosed,
        ],
        'bot_closed' => [ConversationStatus::BotClosed],
        'waiting' => [
            ConversationStatus::Waiting,
            ConversationStatus::InProgress,
            ConversationStatus::Resolved,
            ConversationStatus::Closed,
        ],
        'in_progress' => [
            ConversationStatus::InProgress,
            ConversationStatus::Waiting,
            ConversationStatus::Resolved,
            ConversationStatus::Closed,
        ],
        'resolved' => [
            ConversationStatus::Resolved,
            ConversationStatus::Closed,
        ],
        'closed' => [ConversationStatus::Closed],
    ];

    public function lock(Conversation|int $conversation): Conversation
    {
        $id = $conversation instanceof Conversation ? $conversation->getKey() : $conversation;

        return Conversation::query()->lockForUpdate()->findOrFail($id);
    }

    /**
     * The caller must already be inside a transaction and hold a row lock.
     *
     * @param array<string, mixed> $updates
     */
    public function transitionLocked(
        Conversation $conversation,
        ConversationStatus $to,
        array $updates = [],
    ): Conversation {
        $from = $conversation->status;
        $this->assertAllowed($from, $to);

        $willBeAssigned = array_key_exists('assigned_to', $updates) && $updates['assigned_to'] !== null;
        if (($from === ConversationStatus::BotActive && $to !== ConversationStatus::BotActive) || $willBeAssigned) {
            $this->cancelQueuedBotMessages($conversation, "conversation_transitioned_to_{$to->value}");
        }

        $conversation->forceFill(array_merge($updates, ['status' => $to]))->save();

        if (! $this->isActive($to)) {
            DB::table('conversation_active_cycles')
                ->where('conversation_id', $conversation->id)
                ->delete();
        }

        if ($from !== $to) {
            DB::afterCommit(function () use ($conversation, $from, $to) {
                event(new ConversationStatusChanged($conversation->id, $from, $to));
                event(new ConversationUpdated($conversation->id, 'status_changed'));
            });
        }

        return $conversation;
    }

    public function assertAllowed(ConversationStatus $from, ConversationStatus $to): void
    {
        if (! in_array($to, self::ALLOWED_TRANSITIONS[$from->value] ?? [], true)) {
            throw InvalidConversationTransition::fromStatuses($from, $to);
        }
    }

    public function isActive(ConversationStatus $status): bool
    {
        return in_array($status, [
            ConversationStatus::BotActive,
            ConversationStatus::Waiting,
            ConversationStatus::InProgress,
        ], true);
    }

    public function cancelQueuedBotMessages(Conversation $conversation, string $reason): void
    {
        $messages = $conversation->messages()
            ->where('sender_type', MessageSenderType::Bot)
            ->where('status', MessageStatus::Pending)
            ->lockForUpdate()
            ->get();

        foreach ($messages as $message) {
            $metadata = $message->metadata ?? [];
            $metadata['bot_delivery_guard'] = array_merge(
                $metadata['bot_delivery_guard'] ?? [],
                [
                    'cancelled' => true,
                    'cancelled_reason' => $reason,
                    'cancelled_at' => now()->toIso8601String(),
                ],
            );

            $message->forceFill([
                'status' => MessageStatus::Failed,
                'error_message' => "Envio do bot cancelado: {$reason}",
                'metadata' => $metadata,
            ])->save();
        }
    }
}
