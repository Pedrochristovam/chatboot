<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_internal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('conversations', 'sla_due_at')) {
                $table->timestamp('sla_due_at')->nullable()->after('last_message_at');
            }
            if (! Schema::hasColumn('conversations', 'waiting_since')) {
                $table->timestamp('waiting_since')->nullable()->after('sla_due_at');
            }
        });

        if (! collect(Schema::getIndexes('message_status_events'))->contains(fn ($i) => ($i['name'] ?? '') === 'message_status_events_provider_event_unique')) {
            Schema::table('message_status_events', function (Blueprint $table) {
                $table->unique('provider_event_id', 'message_status_events_provider_event_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('message_status_events', function (Blueprint $table) {
            $table->dropUnique('message_status_events_provider_event_unique');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['sla_due_at', 'waiting_since']);
        });

        Schema::dropIfExists('conversation_internal_notes');
    }
};
