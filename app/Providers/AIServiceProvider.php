<?php

namespace App\Providers;

use Application\Contracts\AI\AIProviderInterface;
use Application\Services\AI\AIService;
use Illuminate\Support\ServiceProvider;
use Infrastructure\AI\NullAIProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AIProviderInterface::class, NullAIProvider::class);
        $this->app->singleton(AIService::class);
    }
}
