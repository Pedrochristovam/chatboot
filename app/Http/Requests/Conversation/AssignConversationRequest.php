<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation !== null
            && $this->user()?->can('assign', $conversation) === true;
    }

    public function rules(): array
    {
        $agentRule = Rule::exists('users', 'id')->whereNull('deleted_at');
        $isPrivileged = $this->user()?->roles()
            ->whereIn('slug', ['super-admin', 'administrador', 'supervisor'])
            ->exists() === true;

        return [
            'agent_id' => $isPrivileged
                ? ['nullable', 'integer', $agentRule]
                : ['sometimes', 'integer', $agentRule, Rule::in([$this->user()->id])],
        ];
    }
}
