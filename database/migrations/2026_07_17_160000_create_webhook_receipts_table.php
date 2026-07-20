<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webhook_receipts')) {
            return;
        }

        Schema::create('webhook_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);
            $table->string('idempotency_key', 191);
            $table->string('event_type', 40);
            $table->string('external_message_id')->nullable();
            $table->string('conversation_key')->nullable();
            $table->string('event_status', 30)->nullable();
            $table->json('payload');
            $table->boolean('signature_valid')->nullable();
            $table->string('processing_status', 30)->default('received');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(
                ['provider', 'idempotency_key'],
                'webhook_receipts_provider_key_unique'
            );
            $table->index(
                ['external_message_id', 'event_type', 'processing_status'],
                'webhook_receipts_message_state_index'
            );
            $table->index(
                ['conversation_key', 'id'],
                'webhook_receipts_conversation_order_index'
            );
            $table->index(
                ['processing_status', 'created_at'],
                'webhook_receipts_processing_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_receipts');
    }
};
