<?php

namespace App\Http\Requests\Tyres;

use App\Enums\TyreLocationType;
use App\Models\TyreBaseline;
use App\Models\Vehicle;
use App\Services\VehicleOdometerService;
use App\Support\TyrePositionHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTyreBaselineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tyre.update') ?? false;
    }

    public function rules(): array
    {
        return [
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
            $baseline = $this->route('baseline');

            if (! $baseline instanceof TyreBaseline) {
                return;
            }

            $locationType = $this->input('baseline_location_type') ?: $baseline->baseline_location_type;
            $locationId = $this->input('baseline_location_id') ?: $baseline->baseline_location_id;
            $positionCode = $this->input('baseline_position_code') ?: $baseline->baseline_position_code;

            if (
                $this->needsRunningOdometer($locationType, $positionCode)
                && ! $this->filled('baseline_odometer')
                && ! $this->hasVehicleOdometerFallback($locationType, $locationId)
            ) {
                $validator->errors()->add(
                    'baseline_odometer',
                    'Baseline odometer is required because this tyre is mounted on a running vehicle position and no vehicle KM is available.'
                );
            }
        });
    }

    private function needsRunningOdometer(?string $locationType, ?string $positionCode): bool
    {
        return in_array($locationType, [TyreLocationType::PowerVehicle->value, TyreLocationType::Trailer->value], true)
            && TyrePositionHelper::isRunningPosition($positionCode);
    }

    private function hasVehicleOdometerFallback(?string $locationType, null|int|string $vehicleId): bool
    {
        if (! $vehicleId || ! in_array($locationType, [TyreLocationType::PowerVehicle->value, TyreLocationType::Trailer->value], true)) {
            return false;
        }

        $vehicle = Vehicle::query()->find((int) $vehicleId);

        return $vehicle && app(VehicleOdometerService::class)->getLatestOdometer($vehicle) !== null;
    }
}
