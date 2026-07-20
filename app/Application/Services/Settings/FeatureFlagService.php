<?php

namespace Application\Services\Settings;

use Illuminate\Support\Facades\Cache;
use Infrastructure\Persistence\Eloquent\Models\Setting;

class FeatureFlagService
{
    public function isEnabled(string $key, bool $default = false): bool
    {
        $value = Setting::getValue('features', $key, $default ? '1' : '0');

        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    public function set(string $key, bool $enabled): void
    {
        Setting::setValue('features', $key, $enabled ? '1' : '0', 'boolean');
        Cache::forget('settings.features.all');
    }

    /** @return array<string, bool> */
    public function all(): array
    {
        return Cache::remember('settings.features.all', now()->addMinutes(10), function () {
            $keys = [
                'realtime',
                'internal_notes',
                'transfers',
                'audit_log',
                'business_hours_bot',
                'message_status_webhooks',
                'bot_panel_simulator',
            ];

            $out = [];
            foreach ($keys as $key) {
                $out[$key] = $this->isEnabled($key, true);
            }

            return $out;
        });
    }
}
