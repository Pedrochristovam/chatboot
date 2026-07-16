<?php

namespace Application\Services\Agent;

use Domain\Shared\Enums\AgentStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Infrastructure\Persistence\Eloquent\Models\Role;
use Infrastructure\Persistence\Eloquent\Models\User;

class AgentService
{
    public function all(): Collection
    {
        return User::query()->with(['roles', 'departments'])->get();
    }

    public function create(array $data): User
    {
        $agent = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_title' => $data['role_title'] ?? 'Atendente',
            'status' => AgentStatus::Offline,
        ]);

        if (! empty($data['role_id'])) {
            $agent->roles()->sync([$data['role_id']]);
        }

        if (! empty($data['department_ids'])) {
            $agent->departments()->sync($data['department_ids']);
        }

        return $agent->load(['roles', 'departments']);
    }

    public function update(User $agent, array $data): User
    {
        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role_title' => $data['role_title'] ?? $agent->role_title,
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $agent->update($update);

        if (array_key_exists('role_id', $data)) {
            $agent->roles()->sync($data['role_id'] ? [$data['role_id']] : []);
        }

        if (array_key_exists('department_ids', $data)) {
            $agent->departments()->sync($data['department_ids'] ?? []);
        }

        return $agent->fresh(['roles', 'departments']);
    }

    public function delete(User $agent): void
    {
        if ($agent->id === auth()->id()) {
            throw new \InvalidArgumentException('Não é possível excluir seu próprio usuário.');
        }

        $agent->delete();
    }

    public function stats(): array
    {
        $agents = User::query()->get();

        return [
            'total' => $agents->count(),
            'online' => $agents->where('status', AgentStatus::Online)->count(),
        ];
    }

    public function roles(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }
}
