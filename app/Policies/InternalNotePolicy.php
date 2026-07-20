<?php

namespace App\Policies;

use App\Policies\Concerns\ChecksAccess;
use Illuminate\Support\Facades\Gate;
use Infrastructure\Persistence\Eloquent\Models\ConversationInternalNote;
use Infrastructure\Persistence\Eloquent\Models\User;

class InternalNotePolicy
{
    use ChecksAccess;

    public function delete(User $user, ConversationInternalNote $note): bool
    {
        if (! Gate::forUser($user)->allows('manageNotes', $note->conversation)) {
            return false;
        }

        return $this->isPrivileged($user) || (int) $note->author_id === (int) $user->id;
    }
}
