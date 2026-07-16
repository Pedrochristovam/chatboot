<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('conversations', 'closed_by')) {
                $table->foreignId('closed_by')
                    ->nullable()
                    ->after('assigned_to')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['status', 'closed_by', 'closed_at'], 'conversations_closed_by_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_closed_by_status_index');
            if (Schema::hasColumn('conversations', 'closed_by')) {
                $table->dropConstrainedForeignId('closed_by');
            }
        });
    }
};
