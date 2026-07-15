<?php

namespace App\Http\Requests\Fleet;

use App\Enums\AssetType;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $currentYear = (int) date('Y');
        $vehicleId = $this->route('vehicle')?->id;

        return [
            'vehicle_code' => [
                'nullable', 'string', 'max:255',
                Rule::unique('vehicles', 'vehicle_code')->ignore($vehicleId),
            ],
            'plate_number' => [
                'nullable', 'string', 'max:255',
                Rule::unique('vehicles', 'plate_number')->ignore($vehicleId),
            ],
            'chassis_number' => [
                'nullable', 'string', 'max:255',
                Rule::unique('vehicles', 'chassis_number')->ignore($vehicleId),
            ],
            'engine_number' => [
                'nullable', 'string', 'max:255',
                Rule::unique('vehicles', 'engine_number')->ignore($vehicleId),
            ],
            'asset_type' => ['required', Rule::enum(AssetType::class)],
            'vehicle_type_id' => ['required', 'exists:vehicle_types,id'],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'current_location_id' => ['nullable', 'exists:locations,id'],
            'manufacture_year' => ['nullable', 'integer', 'min:1980', 'max:'.($currentYear + 1)],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'attached_power_vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'attached_trailer_vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateVehicleSetup($validator);
            },
        ];
    }

    private function validateVehicleSetup(Validator $validator): void
    {
        $assetType = $this->input('asset_type');
        $vehicleType = VehicleType::query()->find($this->input('vehicle_type_id'));

        if ($vehicleType && $assetType && $vehicleType->asset_type->value !== $assetType) {
            $validator->errors()->add('vehicle_type_id', 'Choose a vehicle type that matches the selected asset type.');
        }

        if ($assetType !== AssetType::Trailer->value && $this->filled('attached_power_vehicle_id')) {
            $validator->errors()->add('attached_power_vehicle_id', 'Only trailers can be attached to a power vehicle from this field.');
        }

        if ($assetType !== AssetType::PowerVehicle->value && $this->filled('attached_trailer_vehicle_id')) {
            $validator->errors()->add('attached_trailer_vehicle_id', 'Only power vehicles can attach a trailer from this field.');
        }

        $powerVehicle = Vehicle::query()->find($this->input('attached_power_vehicle_id'));
        if ($powerVehicle && $powerVehicle->asset_type !== AssetType::PowerVehicle) {
            $validator->errors()->add('attached_power_vehicle_id', 'Select a power vehicle.');
        }

        $trailer = Vehicle::query()->find($this->input('attached_trailer_vehicle_id'));
        if ($trailer && $trailer->asset_type !== AssetType::Trailer) {
            $validator->errors()->add('attached_trailer_vehicle_id', 'Select a trailer.');
        }
    }
}
