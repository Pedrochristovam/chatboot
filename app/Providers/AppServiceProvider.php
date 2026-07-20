<?php

namespace App\Providers;

use App\Policies\BotPolicy;
use App\Policies\ClientPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\InternalNotePolicy;
use App\Policies\SettingsPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Persistence\Eloquent\Models\BotKnowledge;
use Infrastructure\Persistence\Eloquent\Models\BotTopic;
use Infrastructure\Persistence\Eloquent\Models\Client;
use Infrastructure\Persistence\Eloquent\Models\Conversation;
use Infrastructure\Persistence\Eloquent\Models\ConversationInternalNote;
use Infrastructure\Persistence\Eloquent\Models\Setting;
use Infrastructure\Persistence\Eloquent\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Queue::before(function () {
            $key = 'operations.queue.last_activity';
            $last = Cache::get($key);
            if ($last && now()->diffInSeconds($last) < 10) {
                return;
            }
            Cache::put($key, now(), now()->addMinutes(10));
        });

        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(ConversationInternalNote::class, InternalNotePolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Setting::class, SettingsPolicy::class);
        Gate::policy(BotKnowledge::class, BotPolicy::class);
        Gate::policy(BotTopic::class, BotPolicy::class);
    }
}
