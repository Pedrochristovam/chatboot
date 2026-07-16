<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_topics', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('transfers_to_human')->default(false);
            $table->timestamps();
        });

        Schema::create('bot_knowledge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_topic_id')->constrained('bot_topics')->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->json('keywords')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_knowledge');
        Schema::dropIfExists('bot_topics');
    }
};
