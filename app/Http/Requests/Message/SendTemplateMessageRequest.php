<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

class SendTemplateMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation !== null
            && $this->user()?->can('sendMessage', $conversation) === true;
    }

    public function rules(): array
    {
        return [
            'template_name' => ['required', 'string', 'max:120'],
            'language' => ['nullable', 'string', 'max:15'],
            'components' => ['nullable', 'array'],
            'body_parameters' => ['nullable', 'array'],
            'body_parameters.*' => ['string', 'max:500'],
        ];
    }
}
