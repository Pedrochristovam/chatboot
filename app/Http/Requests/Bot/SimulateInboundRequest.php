<?php

namespace App\Http\Requests\Bot;

use Illuminate\Foundation\Http\FormRequest;
use Infrastructure\Persistence\Eloquent\Models\BotKnowledge;

class SimulateInboundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', BotKnowledge::class) === true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30'],
            'content' => ['required', 'string', 'max:2000'],
            'name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
