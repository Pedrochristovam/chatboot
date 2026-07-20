<?php

namespace App\Policies\Concerns;

use Infrastructure\Persistence\Eloquent\Models\User;

trait ChecksAccess
{
    protected function isPrivileged(User $user): bool
    {
        return $user->hasRole('super-admin')
            || $user->hasRole('administrador')
            || $user->hasRole('supervisor');
    }
}
