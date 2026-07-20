<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('conversations'))->pluck('name');

            if (! $indexes->contains('conversations_status_sla_last_message_index')) {
                $table->index(
                    ['status', 'sla_due_at', 'last_message_at'],
                    'conversations_status_sla_last_message_index'
                );
            }

            if (! $indexes->contains('conversations_assigned_status_index')) {
                $table->index(
                    ['assigned_to', 'status'],
                    'conversations_assigned_status_index'
                );
            }

            if (! $indexes->contains('conversations_department_status_index')) {
                $table->index(
                    ['department_id', 'status'],
                    'conversations_department_status_index'
                );
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('messages'))->pluck('name');

            if (! $indexes->contains('messages_conversation_created_id_index')) {
                $table->index(
                    ['conversation_id', 'created_at', 'id'],
                    'messages_conversation_created_id_index'
                );
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('users'))->pluck('name');

            if (! $indexes->contains('users_status_last_seen_index')) {
                $table->index(
                    ['status', 'last_seen_at'],
                    'users_status_last_seen_index'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_status_sla_last_message_index');
            $table->dropIndex('conversations_assigned_status_index');
            $table->dropIndex('conversations_department_status_index');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_created_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_status_last_seen_index');
        });
    }
};
