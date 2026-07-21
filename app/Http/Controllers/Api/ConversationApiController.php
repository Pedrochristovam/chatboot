<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\SimulateInboundRequest;
use App\Http\Requests\Conversation\AssignConversationRequest;
use App\Http\Requests\Conversation\CloseConversationRequest;
use App\Http\Requests\Conversation\StoreInternalNoteRequest;
use App\Http\Requests\Conversation\TransferConversationRequest;
use App\Http\Requests\Message\SendMessageRequest;
use App\Http\Requests\Message\SendTemplateMessageRequest;
use App\Http\Requests\Messaging\StoreScheduledMessageRequest;
use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\Services\Conversation\ConversationService;
use Application\Services\Conversation\ConversationTransferService;
use Application\Services\Conversation\InternalNoteService;
use Application\Services\Conversation\MessageService;
use Application\Services\Conversation\SlaService;
use Application\Services\Messaging\CustomerCareWindowService;
use Application\Services\Messaging\ScheduledMessageService;
use Application\Services\Settings\FeatureFlagService;
use Domain\Shared\Enums\AgentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\ConversationInternalNote;
use Infrastructure\Persistence\Eloquent\Models\Department;
use Infrastructure\Persistence\Eloquent\Models\ScheduledMessage;
use Infrastructure\Persistence\Eloquent\Models\User;

class ConversationApiController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly MessageService $messageService,
        private readonly ConversationTransferService $transferService,
        private readonly SlaService $sla,
        private readonly InternalNoteService $noteService,
        private readonly FeatureFlagService $features,
        private readonly CustomerCareWindowService $careWindow,
        private readonly ScheduledMessageService $scheduledMessages,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Conversation::class);

        $page = $this->conversationService->listForInbox(
            $request->only([
                'search',
                'status',
                'closed_by',
                'assigned_to',
                'limit',
                'offset',
            ]),
            $request->user(),
        );

        return response()->json([
            'conversations' => $this->conversationService->toInboxArray($page['items']),
            'meta' => $page['meta'],
            'features' => $this->features->all(),
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Conversation::class);

        $agents = User::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'status', 'role_title'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'role_title' => $u->role_title,
                'online' => $u->status === AgentStatus::Online,
            ]);

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'agents' => $agents,
            'departments' => $departments,
            'current_user_id' => $request->user()->id,
        ]);
    }

    public function card(Conversation $conversation, Request $request): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $card = $this->conversationService->inboxCard($conversation->id, $request->user());
        if (! $card) {
            return response()->json(['message' => 'Conversa não encontrada.'], 404);
        }

        return response()->json(['conversation' => $card]);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $conversation = $this->conversationService->findWithDetails($conversation->id);

        if (! $conversation) {
            return response()->json(['message' => 'Conversa não encontrada.'], 404);
        }

        $this->messageService->markAsRead($conversation);
        $page = $this->messageService->messagesForConversation(
            $conversation,
            beforeId: $request->integer('before_id') ?: null,
            limit: $request->integer('limit', 50) ?: 50,
        );
        $counts = $this->messageService->messageCounts($conversation);

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
                'sla_state' => $this->sla->state($conversation),
                'client_messages' => $counts['client_messages'],
                'bot_messages' => $counts['bot_messages'],
                'agent_messages' => $counts['agent_messages'],
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
                'assigned_to' => $conversation->assigned_to,
                'department_id' => $conversation->department_id,
                'department' => $conversation->department?->name,
                'care_window' => $this->careWindow->snapshot($conversation),
            ],
            'messages' => $page['messages'],
            'messages_meta' => [
                'has_more' => $page['has_more'],
                'next_before_id' => $page['next_before_id'],
            ],
            'internal_notes' => $this->features->isEnabled('internal_notes', true)
                ? $this->noteService->listForConversation($conversation)->map(fn ($n) => $this->noteService->serialize($n))->values()->all()
                : [],
            'scheduled_messages' => $this->scheduledMessages->listForConversation($conversation),
            'features' => $this->features->all(),
        ]);
    }

    public function sendMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->status->isReadOnlyForAgents()) {
            return response()->json([
                'message' => 'Esta conversa está sob atendimento do bot. Você pode visualizar, mas não enviar mensagens.',
                'error' => ['code' => 'conversation_read_only', 'type' => 'domain'],
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
            return response()->json([
                'message' => $e->getMessage(),
                'error' => ['code' => 'conversation_message_forbidden', 'type' => 'domain'],
            ], 403);
        }

        return response()->json($this->messageService->serializeMessage($message), 201);
    }

    public function sendTemplate(SendTemplateMessageRequest $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->status->isReadOnlyForAgents()) {
            return response()->json([
                'message' => 'Conversa somente leitura.',
                'error' => ['code' => 'conversation_read_only', 'type' => 'domain'],
            ], 403);
        }

        $data = $request->validated();

        try {
            $message = $this->messageService->sendTemplateFromAgent(
                $conversation,
                $request->user()->id,
                $data['template_name'],
                $data['language'] ?? 'pt_BR',
                $data['body_parameters'] ?? [],
                $data['components'] ?? [],
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => ['code' => 'template_send_failed', 'type' => 'domain'],
            ], 403);
        }

        return response()->json($this->messageService->serializeMessage($message), 201);
    }

    public function close(Conversation $conversation, CloseConversationRequest $request): JsonResponse
    {
        $this->conversationService->close($conversation, $request->user()->id);

        return response()->json(['message' => 'Conversa encerrada.']);
    }

    public function assign(AssignConversationRequest $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validated();
        try {
            $conversation = $this->conversationService->assign(
                $conversation,
                $data['agent_id'] ?? $request->user()->id,
                $request->user()->id,
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => ['code' => 'conversation_assign_failed', 'type' => 'domain'],
            ], 422);
        }

        return response()->json([
            'message' => 'Conversa atribuída.',
            'conversation' => $this->conversationService->toInboxItem($conversation->loadMissing(['client', 'assignedAgent', 'tags', 'messages'])),
        ]);
    }

    public function transfer(TransferConversationRequest $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validated();

        try {
            $transfer = $this->transferService->transfer(
                conversation: $conversation,
                byUserId: $request->user()->id,
                toAgentId: $data['agent_id'] ?? null,
                toDepartmentId: $data['department_id'] ?? null,
                reason: $data['reason'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => ['code' => 'conversation_transfer_failed', 'type' => 'domain'],
            ], 422);
        }

        return response()->json([
            'message' => 'Conversa transferida.',
            'transfer_id' => $transfer->id,
            'conversation' => $this->conversationService->inboxCard($conversation->id, $request->user()),
        ]);
    }

    public function notes(Conversation $conversation): JsonResponse
    {
        Gate::authorize('manageNotes', $conversation);

        $notes = $this->noteService->listForConversation($conversation);

        return response()->json([
            'notes' => $notes->map(fn ($n) => $this->noteService->serialize($n))->values()->all(),
        ]);
    }

    public function storeNote(StoreInternalNoteRequest $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validated();

        try {
            $note = $this->noteService->create($conversation, $request->user()->id, $data['body']);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => ['code' => 'internal_note_failed', 'type' => 'domain'],
            ], 422);
        }

        return response()->json(['note' => $this->noteService->serialize($note)], 201);
    }

    public function destroyNote(ConversationInternalNote $note): JsonResponse
    {
        Gate::authorize('delete', $note);

        $this->noteService->delete($note);

        return response()->json(['ok' => true]);
    }

    public function storeScheduled(StoreScheduledMessageRequest $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('sendMessage', $conversation);

        $data = $request->validated();

        try {
            $item = $this->scheduledMessages->scheduleForConversation(
                $conversation,
                $request->user()->id,
                $data['content'],
                $data['scheduled_at'],
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => ['code' => 'schedule_failed', 'type' => 'domain'],
            ], 422);
        }

        return response()->json(['scheduled' => $this->scheduledMessages->serialize($item)], 201);
    }

    public function cancelScheduled(ScheduledMessage $scheduled): JsonResponse
    {
        if ($scheduled->conversation_id) {
            $conversation = Conversation::query()->findOrFail($scheduled->conversation_id);
            Gate::authorize('sendMessage', $conversation);
        } elseif (! $this->features->isEnabled('audit_log', true)) {
            abort(403);
        }

        try {
            $this->scheduledMessages->cancel($scheduled);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => ['code' => 'schedule_cancel_failed', 'type' => 'domain'],
            ], 422);
        }

        return response()->json(['ok' => true]);
    }

    /** Simula mensagem de cliente (para testar bot sem WhatsApp real). */
    public function simulateInbound(SimulateInboundRequest $request): JsonResponse
    {
        if (! $this->features->isEnabled('bot_panel_simulator', true)) {
            return response()->json([
                'message' => 'Simulador desativado.',
                'error' => ['code' => 'bot_simulator_disabled', 'type' => 'domain'],
            ], 422);
        }

        $data = $request->validated();

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
