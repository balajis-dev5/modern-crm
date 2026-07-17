<?php

namespace App\Http\Requests\Lead;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['sometimes', Rule::in(Lead::SOURCES)],
            'deal_value' => ['sometimes', 'integer', 'min:0'],
            'owner_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
