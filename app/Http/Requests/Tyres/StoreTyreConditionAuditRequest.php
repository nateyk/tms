<?php

namespace App\Http\Requests\Tyres;

use Illuminate\Foundation\Http\FormRequest;

class StoreTyreConditionAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tyre.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'audited_remaining_percentage' => ['required', 'numeric', 'between:0,100'],
            'inspection_date' => ['required', 'date'],
            'tread_depth' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'condition' => ['nullable', 'string', 'max:80'],
            'reason' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
