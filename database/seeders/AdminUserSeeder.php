<?php

namespace Database\Seeders;

use Domain\Shared\Enums\AgentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Infrastructure\Persistence\Eloquent\Models\Role;
use Infrastructure\Persistence\Eloquent\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@chatflow.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role_title' => 'Administrador',
                'status' => AgentStatus::Online,
                'last_seen_at' => now(),
            ]
        );

        $superAdminRole = Role::query()->where('slug', 'super-admin')->first();

        if ($superAdminRole) {
            $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }
    }
}
