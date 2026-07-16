<?php

namespace Application\Services\Settings;

use Infrastructure\Persistence\Eloquent\Models\Setting;

class SettingsService
{
    public function all(): array
    {
        return [
            'company_name' => Setting::getValue('general', 'company_name', 'MGI chat'),
            'primary_color' => Setting::getValue('general', 'primary_color', '#8B1E3F'),
            'business_start' => Setting::getValue('business_hours', 'start', '08:00'),
            'business_end' => Setting::getValue('business_hours', 'end', '18:00'),
            'auto_reply' => Setting::getValue('notifications', 'auto_reply_message'),
            'ai_enabled' => Setting::getValue('ai', 'enabled', false),
            'bot_enabled' => Setting::getValue('ai', 'bot_enabled', true),
            'whatsapp_driver' => Setting::getValue('whatsapp', 'driver', config('whatsapp.default', 'null')),
            'meta_token' => Setting::getValue('whatsapp', 'meta_token', config('whatsapp.drivers.meta.token', '')),
            'meta_phone_number_id' => Setting::getValue('whatsapp', 'meta_phone_number_id', config('whatsapp.drivers.meta.phone_number_id', '')),
            'webhook_verify_token' => Setting::getValue('whatsapp', 'webhook_verify_token', config('whatsapp.drivers.meta.webhook_verify_token', 'chatflow_webhook_secret')),
            'webhook_callback_url' => rtrim(config('app.url'), '/').'/api/webhook/whatsapp',
        ];
    }

    public function update(array $data): void
    {
        $map = [
            'company_name' => ['general', 'company_name', 'string'],
            'primary_color' => ['general', 'primary_color', 'string'],
            'business_start' => ['business_hours', 'start', 'string'],
            'business_end' => ['business_hours', 'end', 'string'],
            'auto_reply' => ['notifications', 'auto_reply_message', 'string'],
            'ai_enabled' => ['ai', 'enabled', 'boolean'],
            'bot_enabled' => ['ai', 'bot_enabled', 'boolean'],
            'whatsapp_driver' => ['whatsapp', 'driver', 'string'],
            'meta_token' => ['whatsapp', 'meta_token', 'string'],
            'meta_phone_number_id' => ['whatsapp', 'meta_phone_number_id', 'string'],
            'webhook_verify_token' => ['whatsapp', 'webhook_verify_token', 'string'],
        ];

        foreach ($map as $key => [$group, $settingKey, $type]) {
            if (array_key_exists($key, $data)) {
                Setting::setValue($group, $settingKey, $data[$key], $type);
            }
        }
    }
}
