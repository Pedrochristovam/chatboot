<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('group', 'whatsapp')
            ->whereIn('key', ['meta_token', 'meta_app_secret', 'webhook_verify_token'])
            ->where('type', '!=', 'encrypted')
            ->orderBy('id')
            ->each(function ($setting): void {
                if (! filled($setting->value)) {
                    return;
                }

                DB::table('settings')->where('id', $setting->id)->update([
                    'value' => Crypt::encryptString((string) $setting->value),
                    'type' => 'encrypted',
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Credenciais permanecem criptografadas para evitar exposição acidental.
    }
};
