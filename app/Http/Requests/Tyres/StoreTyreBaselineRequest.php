<?php

namespace App\Http\Requests\Tyres;

use App\Enums\TyreLocationType;
use App\Models\Tyre;
use App\Support\TyrePositionHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'baseline_location_type' => ['nullable', Rule::enum(TyreLocationType::class)],
            'baseline_location_id' => ['nullable', 'integer'],
            'baseline_position_code' => ['nullable', 'string', 'max:16'],
            'baseline_odometer' => ['nullable', 'integer', 'min:0'],
            'baseline_percentage' => ['required', 'numeric', 'between:0,100'],
            'expected_life_km' => ['required', 'integer', 'min:1'],
            'baseline_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $tyre = Tyre::query()->find($this->input('tyre_id'));

            if (! $tyre) {
                return;
            }

            $locationType = $this->input('baseline_location_type') ?: $tyre->current_location_type?->value;
            $positionCode = $this->input('baseline_position_code') ?: $tyre->current_position_code;

            if (
                in_array($locationType, [TyreLocationType::PowerVehicle->value, TyreLocationType::Trailer->value], true)
                && TyrePositionHelper::isRunningPosition($positionCode)
                && ! $this->filled('baseline_odometer')
            ) {
                $validator->errors()->add(
                    'baseline_odometer',
                    'Baseline odometer is required when the tyre is mounted on a running vehicle position.'
                );
            }
        });
    }
}
