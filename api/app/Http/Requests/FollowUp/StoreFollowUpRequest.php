<?php

namespace App\Http\Requests\FollowUp;

use Illuminate\Foundation\Http\FormRequest;

class StoreFollowUpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'due_at' => ['required', 'date'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id', 'required_without:lead_id'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id', 'required_without:customer_id'],
            'assigned_to' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
