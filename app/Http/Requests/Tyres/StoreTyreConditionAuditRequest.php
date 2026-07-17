<?php

namespace App\Http\Requests\Tyres;

use App\Enums\TyreLocationType;
use App\Models\Tyre;
use App\Models\Vehicle;
use App\Services\VehicleOdometerService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'audit_odometer' => ['nullable', 'integer', 'min:0'],
            'tread_depth' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'condition' => ['nullable', 'string', 'max:80'],
            'reason' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $odometer = $this->input('audit_odometer');
            if ($odometer === null || $odometer === '') {
                return;
            }

            $tyre = Tyre::query()->find($this->route('tyre')?->id ?? $this->route('tyre'));
            $vehicle = $tyre && in_array($tyre->current_location_type, [TyreLocationType::PowerVehicle, TyreLocationType::Trailer], true)
                ? Vehicle::query()->find($tyre->current_location_id)
                : null;

            if (! $vehicle) {
                return;
            }

            $latest = app(VehicleOdometerService::class)->getLatestOdometer($vehicle);
            if ($latest !== null && (int) $odometer < $latest) {
                $validator->errors()->add(
                    'audit_odometer',
                    "Audit odometer cannot be lower than the vehicle's current KM ({$latest})."
                );
            }
        });
    }
}
