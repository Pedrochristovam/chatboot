<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Infrastructure\Persistence\Eloquent\Models\Permission;
use Infrastructure\Persistence\Eloquent\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'Ver dashboard', 'slug' => 'dashboard.view', 'module' => 'dashboard'],
            ['name' => 'Gerenciar clientes', 'slug' => 'clients.manage', 'module' => 'clients'],
            ['name' => 'Ver conversas', 'slug' => 'conversations.view', 'module' => 'conversations'],
            ['name' => 'Gerenciar conversas', 'slug' => 'conversations.manage', 'module' => 'conversations'],
            ['name' => 'Transferir conversas', 'slug' => 'transfers.manage', 'module' => 'conversations'],
            ['name' => 'Notas internas', 'slug' => 'notes.manage', 'module' => 'conversations'],
            ['name' => 'Gerenciar robô', 'slug' => 'bot.manage', 'module' => 'bot'],
            ['name' => 'Gerenciar atendentes', 'slug' => 'agents.manage', 'module' => 'agents'],
            ['name' => 'Ver relatórios', 'slug' => 'reports.view', 'module' => 'reports'],
            ['name' => 'Gerenciar configurações', 'slug' => 'settings.manage', 'module' => 'settings'],
            ['name' => 'Ver auditoria', 'slug' => 'audit.view', 'module' => 'admin'],
            ['name' => 'Acesso administrativo', 'slug' => 'admin.access', 'module' => 'admin'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        $roles = [
            'super-admin' => [
                'name' => 'Super Admin',
                'description' => 'Acesso total ao sistema',
                'permissions' => Permission::pluck('slug')->all(),
            ],
            'administrador' => [
                'name' => 'Administrador',
                'description' => 'Gerencia equipe e configurações',
                'permissions' => [
                    'dashboard.view', 'clients.manage', 'conversations.manage', 'transfers.manage',
                    'notes.manage', 'bot.manage', 'agents.manage', 'reports.view', 'settings.manage', 'audit.view',
                ],
            ],
            'supervisor' => [
                'name' => 'Supervisor',
                'description' => 'Supervisiona atendimentos do departamento',
                'permissions' => [
                    'dashboard.view', 'clients.manage', 'conversations.manage', 'transfers.manage',
                    'notes.manage', 'reports.view', 'audit.view',
                ],
            ],
            'atendente' => [
                'name' => 'Atendente',
                'description' => 'Atende conversas atribuídas',
                'permissions' => [
                    'dashboard.view', 'conversations.view', 'conversations.manage',
                    'notes.manage', 'clients.manage',
                ],
            ],
        ];

        foreach ($roles as $slug => $roleData) {
            $role = Role::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $roleData['name'], 'description' => $roleData['description']]
            );

            $permissionIds = Permission::query()
                ->whereIn('slug', $roleData['permissions'])
                ->pluck('id');

            $role->permissions()->sync($permissionIds);
        }
    }
}
