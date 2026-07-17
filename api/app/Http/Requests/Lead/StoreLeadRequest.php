<?php

namespace App\Http\Requests\Lead;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['required', Rule::in(Lead::SOURCES)],
            'stage' => ['sometimes', Rule::in(Lead::STAGES)],
            'deal_value' => ['sometimes', 'integer', 'min:0'],
            'owner_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
