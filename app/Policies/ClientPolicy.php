<?php

namespace App\Policies;

use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('clients.manage');
    }

    public function view(User $user, Client $client): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->viewAny($user);
    }
}
