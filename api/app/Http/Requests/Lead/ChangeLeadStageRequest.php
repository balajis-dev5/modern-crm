<?php

namespace App\Http\Requests\Lead;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeLeadStageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'stage' => ['required', Rule::in(Lead::STAGES)],
        ];
    }
}
