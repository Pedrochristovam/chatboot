<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\SendMessageRequest;
use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\Services\Conversation\ConversationService;
use Application\Services\Conversation\ConversationTransferService;
use Application\Services\Conversation\InternalNoteService;
use Application\Services\Conversation\MessageService;
use Application\Services\Settings\FeatureFlagService;
use Domain\Shared\Enums\MessageSenderType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\ConversationInternalNote;

class ConversationApiController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly MessageService $messageService,
        private readonly ConversationTransferService $transferService,
        private readonly InternalNoteService $noteService,
        private readonly FeatureFlagService $features,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $conversations = $this->conversationService->listForInbox($request->only([
            'search',
            'status',
            'closed_by',
            'assigned_to',
        ]));

        return response()->json([
            'conversations' => $this->conversationService->toInboxArray($conversations),
            'features' => $this->features->all(),
        ]);
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $conversation = $this->conversationService->findWithDetails($conversation->id);

        if (! $conversation) {
            return response()->json(['message' => 'Conversa não encontrada.'], 404);
        }

        $this->messageService->markAsRead($conversation);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status->value,
                'status_label' => $conversation->status->label(),
                'is_read_only' => $conversation->status->isReadOnlyForAgents(),
                'is_bot' => $conversation->status->isBot(),
                'bot_closed_at' => $conversation->bot_closed_at?->format('d/m/Y H:i'),
                'closed_at' => $conversation->closed_at?->format('d/m/Y H:i'),
                'closed_by' => $conversation->closedByAgent?->name ?? $conversation->assignedAgent?->name,
                'waiting_since' => $conversation->waiting_since?->format('d/m/Y H:i'),
                'sla_due_at' => $conversation->sla_due_at?->format('d/m/Y H:i'),
                'client_messages' => $conversation->messages()->where('sender_type', MessageSenderType::Client)->count(),
                'bot_messages' => $conversation->messages()->where('sender_type', MessageSenderType::Bot)->count(),
                'agent_messages' => $conversation->messages()->where('sender_type', MessageSenderType::Agent)->count(),
                'client' => [
                    'id' => $conversation->client->id,
                    'name' => $conversation->client->name,
                    'phone' => $conversation->client->phone,
                    'email' => $conversation->client->email,
                    'company' => $conversation->client->company,
                    'notes' => $conversation->client->notes,
                    'tags' => $conversation->client->tags->map(fn ($t) => [
                        'name' => $t->name,
                        'color' => $t->color,
                    ]),
                ],
                'assigned_agent' => $conversation->assignedAgent?->name,
            ],
            'messages' => $this->messageService->messagesForConversation($conversation),
            'internal_notes' => $this->features->isEnabled('internal_notes', true)
                ? $this->noteService->listForConversation($conversation)->map(fn ($n) => $this->noteService->serialize($n))->values()->all()
                : [],
            'features' => $this->features->all(),
        ]);
    }

    public function sendMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->status->isReadOnlyForAgents()) {
            return response()->json([
                'message' => 'Esta conversa está sob atendimento do bot. Você pode visualizar, mas não enviar mensagens.',
            ], 403);
        }

        try {
            if ($request->hasFile('image')) {
                $message = $this->messageService->sendImageFromAgent(
                    $conversation,
                    $request->user()->id,
                    $request->file('image'),
                    $request->input('content'),
                );
            } else {
                $message = $this->messageService->sendFromAgent(
                    $conversation,
                    $request->user()->id,
                    (string) $request->validated('content'),
                );
            }
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json($this->messageService->serializeMessage($message), 201);
    }

    public function close(Conversation $conversation, Request $request): JsonResponse
    {
        $this->conversationService->close($conversation, $request->user()->id);

        return response()->json(['message' => 'Conversa encerrada.']);
    }

    public function assign(Request $request, Conversation $conversation): JsonResponse
    {
        $conversation = $this->conversationService->assign(
            $conversation,
            $request->input('agent_id', $request->user()->id),
            $request->user()->id,
        );

        return response()->json($conversation);
    }

    public function transfer(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $transfer = $this->transferService->transfer(
                conversation: $conversation,
                byUserId: $request->user()->id,
                toAgentId: $data['agent_id'] ?? null,
                toDepartmentId: $data['department_id'] ?? null,
                reason: $data['reason'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Conversa transferida.',
            'transfer_id' => $transfer->id,
            'conversation' => $conversation->fresh(['assignedAgent', 'department']),
        ]);
    }

    public function notes(Conversation $conversation): JsonResponse
    {
        $notes = $this->noteService->listForConversation($conversation);

        return response()->json([
            'notes' => $notes->map(fn ($n) => $this->noteService->serialize($n))->values()->all(),
        ]);
    }

    public function storeNote(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $note = $this->noteService->create($conversation, $request->user()->id, $data['body']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['note' => $this->noteService->serialize($note)], 201);
    }

    public function destroyNote(ConversationInternalNote $note): JsonResponse
    {
        $this->noteService->delete($note);

        return response()->json(['ok' => true]);
    }

    /** Simula mensagem de cliente (para testar bot sem WhatsApp real). */
    public function simulateInbound(Request $request): JsonResponse
    {
        if (! $this->features->isEnabled('bot_panel_simulator', true)) {
            return response()->json(['message' => 'Simulador desativado.'], 422);
        }

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'content' => ['required', 'string', 'max:2000'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $phone = preg_replace('/\D+/', '', $data['phone']) ?: $data['phone'];

        $message = $this->messageService->processIncoming(new IncomingMessageDTO(
            from: $phone,
            messageId: 'sim_'.Str::uuid()->toString(),
            type: 'text',
            content: $data['content'],
            metadata: [
                'contact_name' => $data['name'] ?? 'Cliente Teste',
                'pushName' => $data['name'] ?? 'Cliente Teste',
                'simulated' => true,
            ],
        ));

        return response()->json([
            'message' => $this->messageService->serializeMessage($message),
            'conversation_id' => $message->conversation_id,
        ], 201);
    }
}
