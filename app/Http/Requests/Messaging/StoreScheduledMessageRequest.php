<?php

namespace App\Http\Requests\Messaging;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduledMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sendMessage', $this->route('conversation')) === true
            || $this->user()?->hasPermission('conversations.manage') === true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:4096'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }
}
