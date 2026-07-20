<?php

namespace App\Policies;

use Infrastructure\Persistence\Eloquent\Models\User;

class SettingsPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasPermission('settings.manage');
    }
}
