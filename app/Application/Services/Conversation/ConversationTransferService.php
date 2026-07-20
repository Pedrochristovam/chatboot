<?php

namespace Application\Services\Conversation;

use App\Events\ConversationUpdated;
use Application\Services\Settings\FeatureFlagService;
use Domain\Shared\Enums\ConversationStatus;
use Illuminate\Support\Facades\DB;
use Infrastructure\Logging\AuditLogger;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\ConversationTransfer;
use Infrastructure\Persistence\Eloquent\Models\Department;

class ConversationTransferService
{
    public function __construct(
        private readonly ConversationWorkflowService $workflow,
        private readonly SlaService $sla,
        private readonly FeatureFlagService $features,
        private readonly AuditLogger $audit,
    ) {}

    public function transfer(
        Conversation $conversation,
        int $byUserId,
        ?int $toAgentId = null,
        ?int $toDepartmentId = null,
        ?string $reason = null,
    ): ConversationTransfer {
        if (! $this->features->isEnabled('transfers', true)) {
            throw new \RuntimeException('Transferências estão desativadas.');
        }

        $transferId = DB::transaction(function () use (
            $conversation,
            $byUserId,
            $toAgentId,
            $toDepartmentId,
            $reason,
        ) {
            $locked = $this->workflow->lock($conversation);
            $fromAgentId = $locked->assigned_to;
            $fromDepartmentId = $locked->department_id;
            $resolvedDept = $toDepartmentId
                ?? $fromDepartmentId
                ?? Department::query()->orderBy('id')->value('id');

            if (! $resolvedDept) {
                throw new \RuntimeException('Cadastre ao menos um departamento para transferir conversas.');
            }

            $transfer = ConversationTransfer::query()->create([
                'conversation_id' => $locked->id,
                'from_department_id' => $fromDepartmentId,
                'to_department_id' => $resolvedDept,
                'from_agent_id' => $fromAgentId,
                'to_agent_id' => $toAgentId,
                'reason' => $reason,
                'transferred_by' => $byUserId,
                'created_at' => now(),
            ]);

            $departmentOnly = $toAgentId === null;
            $this->workflow->transitionLocked(
                $locked,
                $departmentOnly ? ConversationStatus::Waiting : ConversationStatus::InProgress,
                [
                    'assigned_to' => $departmentOnly ? null : $toAgentId,
                    'department_id' => $resolvedDept,
                    'is_bot_handled' => false,
                    'waiting_since' => $departmentOnly ? now() : null,
                    'sla_due_at' => $departmentOnly ? $this->sla->dueAt() : null,
                ],
            );

            if ($this->features->isEnabled('audit_log', true)) {
                $this->audit->log('conversation.transferred', $locked, [
                    'assigned_to' => $fromAgentId,
                    'department_id' => $fromDepartmentId,
                ], [
                    'assigned_to' => $toAgentId,
                    'department_id' => $resolvedDept,
                    'reason' => $reason,
                ]);
            }

            DB::afterCommit(fn () => event(new ConversationUpdated($locked->id, 'transferred')));

            return $transfer->id;
        }, 3);

        return ConversationTransfer::query()->findOrFail($transferId);
    }

    public function logAssignment(Conversation $conversation, ?int $fromAgentId, ?int $toAgentId, int $byUserId): void
    {
        if (! $this->features->isEnabled('transfers', true)) {
            return;
        }

        $dept = $conversation->department_id
            ?? Department::query()->orderBy('id')->value('id');

        if (! $dept) {
            return;
        }

        ConversationTransfer::query()->create([
            'conversation_id' => $conversation->id,
            'from_department_id' => $conversation->department_id,
            'to_department_id' => $dept,
            'from_agent_id' => $fromAgentId,
            'to_agent_id' => $toAgentId,
            'reason' => 'Atribuição',
            'transferred_by' => $byUserId,
            'created_at' => now(),
        ]);
    }
}
