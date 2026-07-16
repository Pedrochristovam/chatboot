<?php

namespace Application\Services\Client;

use Domain\Shared\Enums\ClientStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Infrastructure\Persistence\Eloquent\Models\Client;

class ClientService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Client::query()->with('tags')->latest();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['tag_id'])) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $filters['tag_id']));
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): Client
    {
        $client = Client::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'document' => $data['document'] ?? null,
            'company' => $data['company'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? ClientStatus::Active,
        ]);

        if (! empty($data['tag_ids'])) {
            $client->tags()->sync($data['tag_ids']);
        }

        return $client->load('tags');
    }

    public function update(Client $client, array $data): Client
    {
        $client->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'document' => $data['document'] ?? null,
            'company' => $data['company'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? $client->status,
        ]);

        if (array_key_exists('tag_ids', $data)) {
            $client->tags()->sync($data['tag_ids'] ?? []);
        }

        return $client->fresh('tags');
    }

    public function delete(Client $client): void
    {
        $client->delete();
    }

    public function stats(): array
    {
        $total = Client::count();
        $active = Client::where('status', ClientStatus::Active)->count();
        $newThisMonth = Client::whereMonth('created_at', now()->month)->count();
        $vip = Client::whereHas('tags', fn ($q) => $q->where('slug', 'vip'))->count();

        return [
            'total' => $total,
            'active' => $active,
            'new_this_month' => $newThisMonth,
            'activity_rate' => $total > 0 ? round(($active / $total) * 100) : 0,
            'vip' => $vip,
        ];
    }

    public function allTags(): Collection
    {
        return \Infrastructure\Persistence\Eloquent\Models\Tag::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
