<?php

namespace Tests\Feature;

use Application\Services\Messaging\CustomerCareWindowService;
use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Enums\ClientStatus;
use Domain\Shared\Enums\ConversationOrigin;
use Domain\Shared\Enums\ConversationStatus;
use Domain\Shared\Enums\MessageSenderType;
use Domain\Shared\Enums\MessageStatus;
use Domain\Shared\Enums\MessageType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\Role;
use Infrastructure\Persistence\Eloquent\Models\Setting;
use Infrastructure\Persistence\Eloquent\Models\User;
use Tests\TestCase;

class InboxOpsSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_supports_pagination_past_fifty(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->adminUser();

        $client = Client::query()->create([
            'name' => 'Cliente Bulk',
            'phone' => '5531999000001',
            'status' => ClientStatus::Active,
            'source' => 'whatsapp',
        ]);

        for ($i = 0; $i < 55; $i++) {
            Conversation::query()->create([
                'client_id' => $client->id,
                'status' => ConversationStatus::Waiting,
                'origin' => ConversationOrigin::Whatsapp,
                'is_bot_handled' => false,
                'last_message_at' => now()->subMinutes($i),
            ]);
        }

        $first = $this->actingAs($admin)
            ->getJson('/api/internal/conversations?status=waiting&limit=50&offset=0')
            ->assertOk()
            ->json();

        $this->assertCount(50, $first['conversations']);
        $this->assertTrue($first['meta']['has_more']);
        $this->assertSame(55, $first['meta']['total']);

        $second = $this->actingAs($admin)
            ->getJson('/api/internal/conversations?status=waiting&limit=50&offset=50')
            ->assertOk()
            ->json();

        $this->assertCount(5, $second['conversations']);
        $this->assertFalse($second['meta']['has_more']);
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        RateLimiter::clear('admin@example.com|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])->assertRedirect('/login');
        }

        $this->from('/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_customer_care_window_blocks_session_messages_after_24h(): void
    {
        Setting::setValue('whatsapp', 'driver', 'meta');
        $conversation = $this->conversationWithOldClientMessage();

        $this->assertFalse(app(CustomerCareWindowService::class)->isOpen($conversation));

        $this->expectException(\RuntimeException::class);
        app(CustomerCareWindowService::class)->assertCanSendSessionMessage($conversation);
    }

    public function test_settings_page_requires_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $attendant = User::factory()->create();
        $attendant->roles()->attach(Role::query()->where('slug', 'atendente')->firstOrFail());

        $this->actingAs($attendant)
            ->get('/settings')
            ->assertForbidden();
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'administrador')->firstOrFail());

        return $user;
    }

    private function conversationWithOldClientMessage(): Conversation
    {
        $client = Client::query()->create([
            'name' => 'Cliente Antigo',
            'phone' => '5531999111222',
            'status' => ClientStatus::Active,
            'source' => 'whatsapp',
        ]);

        $conversation = Conversation::query()->create([
            'client_id' => $client->id,
            'status' => ConversationStatus::InProgress,
            'origin' => ConversationOrigin::Whatsapp,
            'is_bot_handled' => false,
        ]);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => MessageSenderType::Client,
            'type' => MessageType::Text,
            'content' => 'oi',
            'status' => MessageStatus::Delivered,
        ]);

        Message::query()->whereKey($message->id)->update([
            'created_at' => now()->subHours(25),
            'updated_at' => now()->subHours(25),
        ]);

        return $conversation;
    }
}
