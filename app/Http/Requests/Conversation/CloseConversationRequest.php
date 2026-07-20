<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;

class CloseConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation !== null
            && $this->user()?->can('close', $conversation) === true;
    }

    public function rules(): array
    {
        return [];
    }
}
