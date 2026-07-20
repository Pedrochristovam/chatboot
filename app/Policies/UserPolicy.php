<?php

namespace App\Policies;

use Infrastructure\Persistence\Eloquent\Models\User;

class UserPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermission('agents.manage');
    }

    public function update(User $user, User $agent): bool
    {
        return $user->hasPermission('agents.manage');
    }

    public function delete(User $user, User $agent): bool
    {
        return $user->hasPermission('agents.manage')
            && (int) $user->id !== (int) $agent->id;
    }
}
