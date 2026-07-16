<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_bot_handled')->default(false)->after('status');
            $table->timestamp('bot_closed_at')->nullable()->after('closed_at');
            $table->timestamp('transferred_to_human_at')->nullable()->after('bot_closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['is_bot_handled', 'bot_closed_at', 'transferred_to_human_at']);
        });
    }
};
