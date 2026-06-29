<?php

namespace App\Http\Requests\Tyres;

use App\Enums\TyreLocationType;
use App\Models\TyreMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTyreMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $movement = $this->route('movement');

        return $movement instanceof TyreMovement
            && ($this->user()?->can('update', $movement) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $type = TyreLocationType::tryFrom((string) $this->input('to_location_type'));

        if (in_array($type, [TyreLocationType::MaintenanceCenter, TyreLocationType::DisposalYard], true)) {
            $this->merge(['to_location_id' => 1]);
        }
    }

    public function rules(): array
    {
        $vehicleTypes = [TyreLocationType::PowerVehicle->value, TyreLocationType::Trailer->value];

        return [
            'movement_date' => ['required', 'date'],
            'to_location_type' => ['required', Rule::enum(TyreLocationType::class)],
            'to_location_id' => ['required', 'integer', 'min:1'],
            'to_position_code' => [
                Rule::requiredIf(fn () => in_array($this->input('to_location_type'), $vehicleTypes, true)),
                'nullable',
                'string',
                'max:16',
            ],
            'from_odometer' => ['nullable', 'integer', 'min:0'],
            'to_odometer' => ['nullable', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
