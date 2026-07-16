<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DepartmentSeeder::class,
            TagSeeder::class,
            SettingsSeeder::class,
            AdminUserSeeder::class,
            DemoDataSeeder::class,
            BotKnowledgeSeeder::class,
        ]);
    }
}
