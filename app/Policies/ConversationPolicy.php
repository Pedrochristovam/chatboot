<?php

namespace App\Policies;

use App\Policies\Concerns\ChecksAccess;
use Domain\Shared\Enums\ConversationStatus;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\User;

class ConversationPolicy
{
    use ChecksAccess;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('conversations.view')
            || $user->hasPermission('conversations.manage');
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->viewAny($user) && $this->canAccess($user, $conversation);
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $user->hasPermission('conversations.manage')
            && $this->canAccess($user, $conversation);
    }

    public function close(User $user, Conversation $conversation): bool
    {
        return $this->sendMessage($user, $conversation);
    }

    public function assign(User $user, Conversation $conversation): bool
    {
        return $this->sendMessage($user, $conversation);
    }

    public function transfer(User $user, Conversation $conversation): bool
    {
        return $user->hasPermission('transfers.manage')
            && $this->canAccess($user, $conversation);
    }

    public function manageNotes(User $user, Conversation $conversation): bool
    {
        return $user->hasPermission('notes.manage')
            && $this->canAccess($user, $conversation);
    }

    private function canAccess(User $user, Conversation $conversation): bool
    {
        if ($this->isPrivileged($user)) {
            return true;
        }

        if ((int) $conversation->assigned_to === (int) $user->id) {
            return true;
        }

        if ($conversation->status === ConversationStatus::Waiting && $conversation->assigned_to === null) {
            return true;
        }

        return $conversation->department_id !== null
            && in_array((int) $conversation->department_id, $user->departmentIds(), true);
    }
}
