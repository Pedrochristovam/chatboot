<?php

namespace Tests\Feature;

use App\Events\ConversationUpdated;
use Application\Services\Conversation\AgentPresenceService;
use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Enums\AgentStatus;
use Domain\Shared\Enums\ClientStatus;
use Domain\Shared\Enums\ConversationOrigin;
use Domain\Shared\Enums\ConversationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\Role;
use Infrastructure\Persistence\Eloquent\Models\User;
use Tests\TestCase;

class PresenceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_agent_conversations_return_immediately_to_queue(): void
    {
        Event::fake([ConversationUpdated::class]);
        $agent = User::factory()->create([
            'status' => AgentStatus::Online,
            'last_seen_at' => now(),
        ]);
        $conversation = $this->conversation($agent);

        $count = app(AgentPresenceService::class)->markOffline($agent, 'test');

        $this->assertSame(1, $count);
        $this->assertSame(AgentStatus::Offline, $agent->fresh()->status);
        $this->assertSame(ConversationStatus::Waiting, $conversation->fresh()->status);
        $this->assertNull($conversation->fresh()->assigned_to);
        $this->assertNotNull($conversation->fresh()->sla_due_at);
        Event::assertDispatched(ConversationUpdated::class);
    }

    public function test_agent_cannot_open_another_agents_conversation(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $attendantRole = Role::query()->where('slug', 'atendente')->firstOrFail();
        $owner->roles()->attach($attendantRole);
        $other->roles()->attach($attendantRole);
        $conversation = $this->conversation($owner);

        $this->assertFalse(Gate::forUser($other)->allows('view', $conversation));
        $this->assertFalse(Gate::forUser($other)->allows('sendMessage', $conversation));
        $this->assertTrue(Gate::forUser($owner)->allows('sendMessage', $conversation));
    }

    private function conversation(User $agent): Conversation
    {
        $client = Client::query()->create([
            'name' => 'Cliente',
            'phone' => fake()->unique()->numerify('55319########'),
            'status' => ClientStatus::Active,
            'source' => 'whatsapp',
        ]);

        return Conversation::query()->create([
            'client_id' => $client->id,
            'assigned_to' => $agent->id,
            'status' => ConversationStatus::InProgress,
            'origin' => ConversationOrigin::Whatsapp,
            'is_bot_handled' => false,
        ]);
    }
}
