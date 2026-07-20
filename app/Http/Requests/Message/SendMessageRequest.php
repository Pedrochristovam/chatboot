<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SendMessageRequest extends FormRequest
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
            'content' => ['nullable', 'string', 'max:4096'],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,jpg,png,webp,gif'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasText = filled($this->input('content'));
            $hasImage = $this->hasFile('image');

            if (! $hasText && ! $hasImage) {
                $validator->errors()->add('content', 'Informe uma mensagem ou anexe uma imagem.');
            }
        });
    }
}
