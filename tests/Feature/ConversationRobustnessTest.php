<?php

namespace Tests\Feature;

use Application\Services\Conversation\ConversationService;
use Application\Services\Conversation\MessageService;
use Application\Services\Bot\BotService;
use Application\Services\Settings\BusinessHoursService;
use Domain\Shared\Enums\ClientStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Setting;
use Infrastructure\Persistence\Eloquent\Models\User;
use Tests\TestCase;

class ConversationRobustnessTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalized_phone_has_only_one_active_conversation(): void
    {
        $first = $this->client('(31) 99999-0001');
        $duplicateFormat = $this->client('31999990001');
        $service = app(ConversationService::class);

        $conversation = $service->findOrCreateForClient($first, false);
        $sameConversation = $service->findOrCreateForClient($duplicateFormat, false);

        $this->assertSame($conversation->id, $sameConversation->id);
        $this->assertDatabaseCount('conversation_active_cycles', 1);
    }

    public function test_new_message_cycle_is_created_after_close_without_losing_history(): void
    {
        $client = $this->client('31999990002');
        $agent = User::factory()->create();
        $service = app(ConversationService::class);

        $first = $service->findOrCreateForClient($client, false);
        $service->close($first, $agent->id);
        $next = $service->findOrCreateForClient($client, false);

        $this->assertNotSame($first->id, $next->id);
        $this->assertDatabaseHas('conversations', ['id' => $first->id, 'status' => 'closed']);
        $this->assertDatabaseHas('conversations', ['id' => $next->id, 'status' => 'waiting']);
    }

    public function test_normal_agent_cannot_steal_an_assigned_conversation(): void
    {
        $client = $this->client('31999990003');
        $firstAgent = User::factory()->create();
        $secondAgent = User::factory()->create();
        $service = app(ConversationService::class);
        $conversation = $service->findOrCreateForClient($client, false);
        $service->assign($conversation, $firstAgent->id, $firstAgent->id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('já foi assumida');
        $service->assign($conversation->fresh(), $secondAgent->id, $secondAgent->id);
    }

    public function test_business_minutes_pause_outside_working_hours(): void
    {
        Setting::setValue('business_hours', 'start', '08:00');
        Setting::setValue('business_hours', 'end', '18:00');
        Setting::setValue('business_hours', 'days', [1, 2, 3, 4, 5], 'json');
        $service = app(BusinessHoursService::class);

        $due = $service->addBusinessMinutes(Carbon::parse('2026-07-17 17:50:00'), 30);

        $this->assertSame('2026-07-20 08:20', $due->format('Y-m-d H:i'));
    }

    public function test_complete_bot_queue_agent_media_close_and_return_cycle(): void
    {
        Queue::fake();
        Storage::fake('public');
        Carbon::setTestNow('2026-07-17 10:00:00');
        Setting::setValue('ai', 'bot_enabled', true, 'boolean');
        $client = $this->client('31999990005');
        $agent = User::factory()->create();
        $conversations = app(ConversationService::class);
        $conversation = $conversations->findOrCreateForClient($client, true);

        app(BotService::class)->startConversation($conversation);
        app(BotService::class)->escalateToHuman($conversation);
        $conversation = $conversations->assign($conversation->fresh(), $agent->id, $agent->id);
        $message = app(MessageService::class)->sendImageFromAgent(
            $conversation,
            $agent->id,
            UploadedFile::fake()->create('comprovante.jpg', 10, 'image/jpeg'),
            'Segue o comprovante',
        );
        $conversations->close($conversation->fresh(), $agent->id);
        $next = $conversations->findOrCreateForClient($client, true);

        $this->assertCount(1, $message->attachments);
        $this->assertNotSame($conversation->id, $next->id);
        $this->assertDatabaseHas('conversations', ['id' => $conversation->id, 'status' => 'closed']);
        $this->assertDatabaseHas('conversations', ['id' => $next->id, 'status' => 'bot_active']);
        Carbon::setTestNow();
    }

    private function client(string $phone): Client
    {
        return Client::query()->create([
            'name' => 'Cliente Teste',
            'phone' => $phone,
            'status' => ClientStatus::Active,
            'source' => 'whatsapp',
        ]);
    }
}
