<?php

namespace Database\Seeders;

use Domain\Shared\Enums\TagType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Infrastructure\Persistence\Eloquent\Models\Tag;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'Urgente', 'color' => '#ef4444'],
            ['name' => 'VIP', 'color' => '#f59e0b'],
            ['name' => 'Cobrança', 'color' => '#dc2626'],
            ['name' => 'Financeiro', 'color' => '#10b981'],
            ['name' => 'Lead', 'color' => '#3b82f6'],
            ['name' => 'Cliente', 'color' => '#8B1E3F'],
        ];

        foreach ($tags as $tag) {
            Tag::query()->firstOrCreate(
                ['slug' => Str::slug($tag['name'])],
                [
                    'name' => $tag['name'],
                    'color' => $tag['color'],
                    'type' => TagType::System,
                    'is_active' => true,
                ]
            );
        }
    }
}
