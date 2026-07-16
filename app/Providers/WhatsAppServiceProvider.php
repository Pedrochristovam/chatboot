<?php

namespace App\Providers;

use Application\Contracts\AI\AIProviderInterface;
use Application\Contracts\WhatsApp\WhatsAppProviderInterface;
use Application\Services\AI\AIService;
use Illuminate\Support\ServiceProvider;
use Infrastructure\AI\NullAIProvider;
use Infrastructure\WhatsApp\WhatsAppProviderFactory;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsAppProviderFactory::class);

        $this->app->bind(WhatsAppProviderInterface::class, function ($app) {
            return $app->make(WhatsAppProviderFactory::class)->make();
        });
    }
}
