<?php

namespace Tests\Feature;

use Application\DTOs\WhatsApp\IncomingMessageDTO;
use Application\Services\Conversation\MessageService;
use Application\Services\Conversation\MessageStatusService;
use Application\Services\Messaging\FailedMessageService;
use Domain\Shared\Enums\ClientStatus;
use Domain\Shared\Enums\ConversationOrigin;
use Domain\Shared\Enums\ConversationStatus;
use Domain\Shared\Enums\MessageSenderType;
use Domain\Shared\Enums\MessageStatus;
use Domain\Shared\Enums\MessageType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\Message;
use Infrastructure\Persistence\Eloquent\Models\Setting;
use Tests\TestCase;

class MessagingReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_inbound_message_is_idempotent(): void
    {
        Setting::setValue('ai', 'bot_enabled', false, 'boolean');
        $dto = new IncomingMessageDTO(
            from: '5531999990004',
            messageId: 'wamid.duplicate',
            type: 'text',
            content: 'Olá',
            metadata: ['contact_name' => 'Cliente'],
        );
        $service = app(MessageService::class);

        $first = $service->processIncoming($dto);
        $duplicate = $service->processIncoming($dto);

        $this->assertSame($first->id, $duplicate->id);
        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_late_status_cannot_regress_a_read_message(): void
    {
        $message = $this->outboundMessage(MessageStatus::Pending, 'wamid.status');
        $service = app(MessageStatusService::class);

        $service->applyWebhookStatus('wamid.status', 'read', 'read-event');
        $service->applyWebhookStatus('wamid.status', 'sent', 'late-sent-event');

        $this->assertSame(MessageStatus::Read, $message->fresh()->status);
        $this->assertDatabaseCount('message_status_events', 2);
    }

    public function test_manual_retry_is_blocked_after_meta_confirmation(): void
    {
        $message = $this->outboundMessage(MessageStatus::Failed, 'wamid.confirmed');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('já confirmou');
        app(FailedMessageService::class)->retry($message);
    }

    public function test_failed_unconfirmed_message_can_be_requeued_once(): void
    {
        Queue::fake();
        $message = $this->outboundMessage(MessageStatus::Failed);

        app(FailedMessageService::class)->retry($message);

        $this->assertSame(MessageStatus::Pending, $message->fresh()->status);
        $this->assertSame(1, $message->fresh()->metadata['manual_retry_count']);
    }

    public function test_invalid_meta_signature_is_rejected_when_secret_is_configured(): void
    {
        Setting::setValue('whatsapp', 'meta_app_secret', 'top-secret', 'encrypted');

        $this->postJson('/api/webhook/whatsapp', ['entry' => []], [
            'X-Hub-Signature-256' => 'sha256=invalid',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('webhook_receipts', 0);
    }

    public function test_valid_webhook_is_accepted_quickly_without_sync_parse(): void
    {
        Queue::fake();
        Setting::setValue('whatsapp', 'driver', 'null');

        $this->postJson('/api/webhook/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '5531999990009',
                            'id' => 'wamid.fast',
                            'timestamp' => (string) time(),
                            'type' => 'text',
                            'text' => ['body' => 'Oi'],
                        ]],
                    ],
                ]],
            ]],
        ])->assertStatus(202)->assertJson(['status' => 'queued']);

        $this->assertDatabaseHas('webhook_receipts', [
            'event_type' => 'request',
            'processing_status' => 'received',
        ]);
    }

    private function outboundMessage(MessageStatus $status, ?string $whatsappId = null): Message
    {
        $client = Client::query()->create([
            'name' => 'Cliente',
            'phone' => fake()->unique()->numerify('55319########'),
            'status' => ClientStatus::Active,
            'source' => 'whatsapp',
        ]);
        $conversation = Conversation::query()->create([
            'client_id' => $client->id,
            'status' => ConversationStatus::InProgress,
            'origin' => ConversationOrigin::Whatsapp,
            'is_bot_handled' => false,
        ]);

        return Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => MessageSenderType::Agent,
            'type' => MessageType::Text,
            'content' => 'Teste',
            'status' => $status,
            'whatsapp_message_id' => $whatsappId,
            'metadata' => [],
        ]);
    }
}
