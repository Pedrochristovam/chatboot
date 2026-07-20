<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Endurece o schema do MGI chat para produção:
 * histórico de contatos/conversas/msgs, bot, agendamentos e rastreio de status.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->upgradeClients();
        $this->upgradeConversations();
        $this->upgradeMessages();
        $this->upgradeBotTables();
        $this->createScheduledMessages();
        $this->createMessageStatusEvents();
    }

    public function down(): void
    {
        Schema::dropIfExists('message_status_events');
        Schema::dropIfExists('scheduled_messages');

        Schema::table('messages', function (Blueprint $table) {
            foreach (['error_message', 'sent_at', 'delivered_at'] as $col) {
                if (Schema::hasColumn('messages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            foreach (['phone_normalized', 'source', 'whatsapp_name'] as $col) {
                if (Schema::hasColumn('clients', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function upgradeClients(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'phone_normalized')) {
                $table->string('phone_normalized', 20)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('clients', 'source')) {
                $table->string('source', 40)->default('whatsapp')->after('status');
            }
            if (! Schema::hasColumn('clients', 'whatsapp_name')) {
                $table->string('whatsapp_name')->nullable()->after('name');
            }
        });

        $clients = DB::table('clients')->select('id', 'phone')->get();
        foreach ($clients as $client) {
            DB::table('clients')->where('id', $client->id)->update([
                'phone_normalized' => preg_replace('/\D+/', '', (string) $client->phone) ?: null,
            ]);
        }

        $this->addIndexIfMissing('clients', 'clients_phone_normalized_index', ['phone_normalized']);
        $this->addIndexIfMissing('clients', 'clients_source_index', ['source']);
        $this->addIndexIfMissing('clients', 'clients_deleted_at_index', ['deleted_at']);
        $this->addIndexIfMissing('clients', 'clients_document_index', ['document']);
    }

    private function upgradeConversations(): void
    {
        $this->addIndexIfMissing('conversations', 'conversations_client_status_index', ['client_id', 'status']);
        $this->addIndexIfMissing('conversations', 'conversations_status_last_message_index', ['status', 'last_message_at']);
        $this->addIndexIfMissing('conversations', 'conversations_bot_status_index', ['is_bot_handled', 'status']);
        $this->addIndexIfMissing('conversations', 'conversations_bot_closed_at_index', ['bot_closed_at']);
        $this->addIndexIfMissing('conversations', 'conversations_transferred_at_index', ['transferred_to_human_at']);
    }

    private function upgradeMessages(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'error_message')) {
                $table->text('error_message')->nullable()->after('status');
            }
            if (! Schema::hasColumn('messages', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('read_at');
            }
            if (! Schema::hasColumn('messages', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('sent_at');
            }
        });

        $this->addIndexIfMissing('messages', 'messages_status_index', ['status']);
        $this->addIndexIfMissing('messages', 'messages_sender_created_index', ['sender_type', 'created_at']);
    }

    private function upgradeBotTables(): void
    {
        $this->addIndexIfMissing('bot_topics', 'bot_topics_active_sort_index', ['is_active', 'sort_order']);
        $this->addIndexIfMissing('bot_knowledge', 'bot_knowledge_topic_active_index', ['bot_topic_id', 'is_active']);
        $this->addIndexIfMissing('bot_knowledge', 'bot_knowledge_is_active_index', ['is_active']);
    }

    private function createScheduledMessages(): void
    {
        if (Schema::hasTable('scheduled_messages')) {
            return;
        }

        Schema::create('scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 30)->default('whatsapp');
            $table->string('type', 30)->default('text');
            $table->text('content');
            $table->json('payload')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 30)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->foreignId('message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at'], 'scheduled_messages_due_index');
            $table->index(['client_id', 'status'], 'scheduled_messages_client_status_index');
            $table->index('scheduled_at', 'scheduled_messages_scheduled_at_index');
        });
    }

    private function createMessageStatusEvents(): void
    {
        if (Schema::hasTable('message_status_events')) {
            return;
        }

        Schema::create('message_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30);
            $table->string('provider_event_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['message_id', 'occurred_at'], 'message_status_events_message_time_index');
            $table->index('status', 'message_status_events_status_index');
        });
    }

    /** @param list<string> $columns */
    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        $exists = collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => ($index['name'] ?? null) === $indexName);

        if ($exists) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }
};
