<?php

namespace Application\Services\Conversation;

use Application\Services\Settings\FeatureFlagService;
use Infrastructure\Logging\AuditLogger;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\ConversationInternalNote;
use Illuminate\Support\Collection;

class InternalNoteService
{
    public function __construct(
        private readonly FeatureFlagService $features,
        private readonly AuditLogger $audit,
    ) {}

    public function listForConversation(Conversation $conversation): Collection
    {
        return ConversationInternalNote::query()
            ->with('author:id,name')
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('id')
            ->get();
    }

    public function create(Conversation $conversation, int $authorId, string $body): ConversationInternalNote
    {
        if (! $this->features->isEnabled('internal_notes', true)) {
            throw new \RuntimeException('Notas internas estão desativadas.');
        }

        $note = ConversationInternalNote::query()->create([
            'conversation_id' => $conversation->id,
            'author_id' => $authorId,
            'body' => trim($body),
        ]);

        if ($this->features->isEnabled('audit_log', true)) {
            $this->audit->log('conversation.note_created', $note);
        }

        return $note->load('author:id,name');
    }

    public function delete(ConversationInternalNote $note): void
    {
        if ($this->features->isEnabled('audit_log', true)) {
            $this->audit->log('conversation.note_deleted', $note, ['body' => $note->body], null);
        }

        $note->delete();
    }

    public function serialize(ConversationInternalNote $note): array
    {
        return [
            'id' => $note->id,
            'body' => $note->body,
            'author' => $note->author?->name,
            'author_id' => $note->author_id,
            'created_at' => $note->created_at?->format('d/m/Y H:i'),
        ];
    }
}
