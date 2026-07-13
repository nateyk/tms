<?php

namespace App\Http\Requests\Tyres;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTyreBaselineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tyre.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'tyre_id' => ['required', 'integer', 'exists:tyres,id', 'unique:tyre_baselines,tyre_id'],
            'baseline_location_type' => ['required', 'string'],
            'baseline_location_id' => ['nullable', 'integer'],
            'baseline_position_code' => ['nullable', 'string', 'max:16'],
            'baseline_odometer' => ['nullable', 'integer', 'min:0'],
            'baseline_percentage' => ['required', 'numeric', 'between:0,100'],
            'expected_life_km' => ['required', 'integer', 'min:1'],
            'baseline_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
