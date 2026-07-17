<?php

namespace App\Http\Requests\FollowUp;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFollowUpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'due_at' => ['sometimes', 'date'],
            'assigned_to' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
