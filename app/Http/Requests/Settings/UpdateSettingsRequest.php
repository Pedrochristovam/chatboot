<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Infrastructure\Persistence\Eloquent\Models\Setting;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Setting::class) === true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:7'],
            'business_start' => ['nullable', 'string', 'max:5'],
            'business_end' => ['nullable', 'string', 'max:5'],
            'sla_first_response_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'auto_reply' => ['nullable', 'string', 'max:1000'],
            'ai_enabled' => ['nullable', 'boolean'],
            'bot_enabled' => ['nullable', 'boolean'],
            'whatsapp_driver' => ['nullable', 'string', 'in:null,meta'],
            'meta_token' => ['nullable', 'string', 'max:500'],
            'meta_phone_number_id' => ['nullable', 'string', 'max:100'],
            'meta_app_secret' => ['nullable', 'string', 'max:255'],
            'webhook_verify_token' => ['nullable', 'string', 'max:100'],
        ];
    }
}
