<?php

namespace App\Http\Requests\Fleet;

use App\Enums\AssetType;
use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
}
