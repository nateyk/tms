<?php

namespace App\Http\Requests\Fleet;

use App\Enums\AssetType;
use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $currentYear = (int) date('Y');

        return [
            'vehicle_code' => ['nullable', 'string', 'max:255', 'unique:vehicles,vehicle_code'],
            'plate_number' => ['nullable', 'string', 'max:255', 'unique:vehicles,plate_number'],
            'chassis_number' => ['nullable', 'string', 'max:255', 'unique:vehicles,chassis_number'],
            'engine_number' => ['nullable', 'string', 'max:255', 'unique:vehicles,engine_number'],
            'asset_type' => ['required', Rule::enum(AssetType::class)],
            'vehicle_type_id' => ['required', 'exists:vehicle_types,id'],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'current_location_id' => ['nullable', 'exists:locations,id'],
            'manufacture_year' => ['nullable', 'integer', 'min:1980', 'max:'.($currentYear + 1)],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
