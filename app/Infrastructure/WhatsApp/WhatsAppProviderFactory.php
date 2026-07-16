<?php

namespace Infrastructure\WhatsApp;

use Application\Contracts\WhatsApp\WhatsAppProviderInterface;
use Application\Services\WhatsApp\WhatsAppConfigService;
use InvalidArgumentException;

class WhatsAppProviderFactory
{
    public function make(?string $driver = null): WhatsAppProviderInterface
    {
        $driver ??= app(WhatsAppConfigService::class)->driver();

        return match ($driver) {
            'null' => app(NullWhatsAppProvider::class),
            'meta' => app(MetaCloudProvider::class),
            'evolution' => app(EvolutionApiProvider::class),
            'zapi' => app(ZApiProvider::class),
            'baileys' => app(BaileysProvider::class),
            default => throw new InvalidArgumentException("WhatsApp driver [{$driver}] não suportado."),
        };
    }
}
