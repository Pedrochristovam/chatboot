<?php

namespace Application\Services\WhatsApp;

use Infrastructure\Persistence\Eloquent\Models\Setting;

class WhatsAppConfigService
{
    public function driver(): string
    {
        return (string) (Setting::getValue('whatsapp', 'driver', config('whatsapp.default')) ?: 'null');
    }

    public function metaToken(): ?string
    {
        return Setting::getValue('whatsapp', 'meta_token')
            ?: config('whatsapp.drivers.meta.token');
    }

    public function metaPhoneNumberId(): ?string
    {
        return Setting::getValue('whatsapp', 'meta_phone_number_id')
            ?: config('whatsapp.drivers.meta.phone_number_id');
    }

    public function webhookVerifyToken(): ?string
    {
        return Setting::getValue('whatsapp', 'webhook_verify_token')
            ?: config('whatsapp.drivers.meta.webhook_verify_token');
    }

    public function metaAppSecret(): ?string
    {
        return Setting::getValue('whatsapp', 'meta_app_secret')
            ?: config('whatsapp.drivers.meta.app_secret');
    }

    public function webhookCallbackUrl(): string
    {
        return rtrim(config('app.url'), '/').'/api/webhook/whatsapp';
    }

    public function isMetaConfigured(): bool
    {
        return $this->driver() === 'meta'
            && filled($this->metaToken())
            && filled($this->metaPhoneNumberId())
            && filled($this->metaAppSecret());
    }

    public function status(): array
    {
        return [
            'driver' => $this->driver(),
            'configured' => $this->driver() === 'null' || $this->isMetaConfigured(),
            'webhook_url' => $this->webhookCallbackUrl(),
            'verify_token' => $this->webhookVerifyToken(),
        ];
    }
}
