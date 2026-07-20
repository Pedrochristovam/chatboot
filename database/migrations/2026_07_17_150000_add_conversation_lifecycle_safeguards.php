<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_phone_identities', function (Blueprint $table) {
            $table->string('normalized_phone', 32)->primary();
            $table->foreignId('canonical_client_id')->constrained('clients')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('conversation_active_cycles', function (Blueprint $table) {
            $table->string('normalized_phone', 32)->primary();
            $table->foreignId('conversation_id')->unique()->constrained('conversations')->cascadeOnDelete();
            $table->timestamps();
        });

        $this->backfillPhoneIdentities();
        $this->backfillActiveCycles();
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_active_cycles');
        Schema::dropIfExists('client_phone_identities');
    }

    private function backfillPhoneIdentities(): void
    {
        $clients = DB::table('clients')
            ->select(['id', 'phone', 'phone_normalized', 'deleted_at'])
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->get();

        $seen = [];
        $now = now();

        foreach ($clients as $client) {
            $normalized = $this->normalizePhone($client->phone_normalized ?: $client->phone);
            if ($normalized === null || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            DB::table('client_phone_identities')->insert([
                'normalized_phone' => $normalized,
                'canonical_client_id' => $client->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function backfillActiveCycles(): void
    {
        $activeStatuses = ['bot_active', 'waiting', 'in_progress'];
        $conversations = DB::table('conversations')
            ->join('clients', 'clients.id', '=', 'conversations.client_id')
            ->whereNull('conversations.deleted_at')
            ->whereIn('conversations.status', $activeStatuses)
            ->select([
                'conversations.id',
                'clients.phone',
                'clients.phone_normalized',
            ])
            ->orderByDesc('conversations.last_message_at')
            ->orderByDesc('conversations.id')
            ->get();

        $seen = [];
        $now = now();

        foreach ($conversations as $conversation) {
            $normalized = $this->normalizePhone($conversation->phone_normalized ?: $conversation->phone);
            if ($normalized === null) {
                continue;
            }
            if (isset($seen[$normalized])) {
                DB::table('conversations')->where('id', $conversation->id)->update([
                    'status' => 'closed',
                    'closed_at' => $now,
                    'resolved_at' => $now,
                    'assigned_to' => null,
                    'is_bot_handled' => false,
                    'waiting_since' => null,
                    'sla_due_at' => null,
                    'updated_at' => $now,
                ]);
                continue;
            }

            $seen[$normalized] = true;
            DB::table('conversation_active_cycles')->insert([
                'normalized_phone' => $normalized,
                'conversation_id' => $conversation->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        $normalized = preg_replace('/\D+/', '', (string) $phone) ?: '';

        return $normalized !== '' ? $normalized : null;
    }
};
