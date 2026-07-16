<?php

namespace App\Console\Commands;

use Application\Services\WhatsApp\WhatsAppConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class WhatsAppStatusCommand extends Command
{
    protected $signature = 'whatsapp:status';

    protected $description = 'Verifica a configuração do WhatsApp Meta Cloud API';

    public function handle(WhatsAppConfigService $config): int
    {
        $this->info('MGI chat — Status WhatsApp');
        $this->line('Driver: '.$config->driver());
        $this->line('Webhook: '.$config->webhookCallbackUrl());

        if ($config->driver() !== 'meta') {
            $this->warn('Modo simulado. Altere para "meta" em Configurações → WhatsApp Meta.');

            return self::SUCCESS;
        }

        if (! $config->isMetaConfigured()) {
            $this->error('Token ou Phone Number ID não configurados.');

            return self::FAILURE;
        }

        $response = Http::withToken($config->metaToken())
            ->get('https://graph.facebook.com/v21.0/'.$config->metaPhoneNumberId());

        if ($response->successful()) {
            $this->info('Conexão com Meta OK!');
            $this->line('Número: '.($response->json('display_phone_number') ?? '—'));

            return self::SUCCESS;
        }

        $this->error('Erro Meta: '.($response->json('error.message') ?? $response->body()));

        return self::FAILURE;
    }
}
