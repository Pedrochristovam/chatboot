<?php

namespace App\Http\Requests\Bot;

use Illuminate\Foundation\Http\FormRequest;
use Infrastructure\Persistence\Eloquent\Models\BotKnowledge;

class ManageBotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', BotKnowledge::class) === true;
    }

    public function rules(): array
    {
        return [];
    }
}
