<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Infrastructure\Persistence\Eloquent\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'general', 'key' => 'company_name', 'value' => 'MGI chat', 'type' => 'string'],
            ['group' => 'general', 'key' => 'primary_color', 'value' => '#8B1E3F', 'type' => 'string'],
            ['group' => 'general', 'key' => 'logo', 'value' => '', 'type' => 'file'],
            ['group' => 'business_hours', 'key' => 'start', 'value' => '08:00', 'type' => 'string'],
            ['group' => 'business_hours', 'key' => 'end', 'value' => '18:00', 'type' => 'string'],
            ['group' => 'business_hours', 'key' => 'days', 'value' => [1, 2, 3, 4, 5], 'type' => 'json'],
            ['group' => 'sla', 'key' => 'first_response_minutes', 'value' => 15, 'type' => 'integer'],
            ['group' => 'notifications', 'key' => 'auto_reply_message', 'value' => 'Olá! Recebemos sua mensagem e em breve um atendente irá respondê-lo.', 'type' => 'string'],
            ['group' => 'whatsapp', 'key' => 'driver', 'value' => 'null', 'type' => 'string'],
            ['group' => 'whatsapp', 'key' => 'meta_token', 'value' => '', 'type' => 'encrypted'],
            ['group' => 'whatsapp', 'key' => 'meta_phone_number_id', 'value' => '', 'type' => 'string'],
            ['group' => 'whatsapp', 'key' => 'meta_app_secret', 'value' => '', 'type' => 'encrypted'],
            ['group' => 'whatsapp', 'key' => 'webhook_verify_token', 'value' => 'chatflow_webhook_secret', 'type' => 'encrypted'],
            ['group' => 'ai', 'key' => 'enabled', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'ai', 'key' => 'bot_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'ai', 'key' => 'model', 'value' => 'gpt-4o-mini', 'type' => 'string'],
            ['group' => 'notifications', 'key' => 'bot_welcome_message', 'value' => 'Olá! 👋 Sou o assistente virtual. Posso ajudar com horário, boletos e pedidos. Digite *atendente* para falar com um humano.', 'type' => 'string'],
            ['group' => 'notifications', 'key' => 'bot_transfer_message', 'value' => 'Entendido! Vou transferir você para um atendente. Aguarde um momento. ⏳', 'type' => 'string'],
            ['group' => 'notifications', 'key' => 'bot_closed_message', 'value' => 'Fico feliz em ter ajudado! Se precisar de mais algo, envie uma nova mensagem. Até logo! 👋', 'type' => 'string'],
            ['group' => 'notifications', 'key' => 'after_hours_message', 'value' => 'Olá! Nosso horário de atendimento é de segunda a sexta, das 08:00 às 18:00. Retornaremos assim que possível.', 'type' => 'string'],
            ['group' => 'features', 'key' => 'realtime', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'internal_notes', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'transfers', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'audit_log', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'business_hours_bot', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'message_status_webhooks', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'bot_panel_simulator', 'value' => '1', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            Setting::setValue($setting['group'], $setting['key'], $setting['value'], $setting['type']);
        }
    }
}
