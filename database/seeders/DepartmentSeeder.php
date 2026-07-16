<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Infrastructure\Persistence\Eloquent\Models\Department;
use Infrastructure\Persistence\Eloquent\Models\Queue;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Comercial', 'color' => '#3b82f6'],
            ['name' => 'Financeiro', 'color' => '#10b981'],
            ['name' => 'Suporte', 'color' => '#f59e0b'],
            ['name' => 'RH', 'color' => '#8b5cf6'],
            ['name' => 'Jurídico', 'color' => '#ef4444'],
            ['name' => 'Administrador', 'color' => '#8B1E3F'],
        ];

        foreach ($departments as $department) {
            $dept = Department::query()->firstOrCreate(
                ['slug' => Str::slug($department['name'])],
                [
                    'name' => $department['name'],
                    'color' => $department['color'],
                    'description' => "Departamento de {$department['name']}",
                    'is_active' => true,
                ]
            );

            Queue::query()->firstOrCreate(
                ['name' => "Fila {$department['name']}", 'department_id' => $dept->id],
                ['priority' => 0, 'is_active' => true]
            );
        }
    }
}
