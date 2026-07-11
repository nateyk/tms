<?php

namespace App\Http\Requests\Tyres;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTyreBaselineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tyre-reading.baseline.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'baseline_percentage' => ['required', 'numeric', 'between:0,100'],
            'expected_life_km' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
