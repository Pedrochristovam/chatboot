<?php

namespace App\Policies;

use Infrastructure\Persistence\Eloquent\Models\User;

class BotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('bot.manage');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermission('bot.manage');
    }

    public function update(User $user, object $model): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, object $model): bool
    {
        return $this->manage($user);
    }
}
