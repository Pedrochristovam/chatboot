<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;

class TransferConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation !== null
            && $this->user()?->can('transfer', $conversation) === true;
    }

    public function rules(): array
    {
        return [
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
