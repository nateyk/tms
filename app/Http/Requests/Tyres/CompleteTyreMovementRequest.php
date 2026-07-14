<?php

namespace App\Http\Requests\Tyres;

use App\Enums\TyreLocationType;
use App\Exceptions\TyreBusinessException;
use App\Models\TyreMovement;
use App\Models\Vehicle;
use App\Services\VehicleOdometerService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CompleteTyreMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('movement.approve');
    }

    public function rules(): array
    {
        return [
            'from_odometer' => ['nullable', 'integer', 'min:0'],
            'to_odometer' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $movement = $this->route('movement');

            if (!$movement instanceof TyreMovement) {
                return;
            }

            $this->validateOdometerRequirements($validator, $movement);
            $this->validateOdometerValues($validator, $movement);
        });
    }

    protected function validateOdometerRequirements(Validator $validator, TyreMovement $movement): void
    {
        $sourceType = $movement->source_location_type;
        $destinationType = $movement->destination_location_type;

        // Require from_odometer if source is vehicle
        if (in_array($sourceType, [TyreLocationType::PowerVehicle, TyreLocationType::Trailer], true)) {
            if (!$this->filled('from_odometer')) {
                $validator->errors()->add('from_odometer', 'Source odometer is required when moving from a vehicle.');
            }
        }

        // Require to_odometer if destination is vehicle
        if (in_array($destinationType, [TyreLocationType::PowerVehicle, TyreLocationType::Trailer], true)) {
            if (!$this->filled('to_odometer')) {
                $validator->errors()->add('to_odometer', 'Destination odometer is required when moving to a vehicle.');
            }
        }
    }

    protected function validateOdometerValues(Validator $validator, TyreMovement $movement): void
    {
        $tyre = $movement->tyre;
        $fromOdometer = $this->input('from_odometer');
        $toOdometer = $this->input('to_odometer');
        $odometerService = app(VehicleOdometerService::class);

        // Validate from_odometer against active assignment
        if ($fromOdometer !== null && $tyre->activeAssignment) {
            $installedOdometer = $tyre->activeAssignment->installed_odometer ?? 0;
            if ($fromOdometer < $installedOdometer) {
                $validator->errors()->add(
                    'from_odometer',
                    "Source odometer ({$fromOdometer}) cannot be lower than the installed odometer ({$installedOdometer})."
                );
            }
        }

        // Validate from_odometer against source vehicle latest odometer
        $sourceVehicle = $this->movementVehicle($movement->from_location_type, $movement->from_location_id);
        if ($fromOdometer !== null && $sourceVehicle) {
            $latestSourceOdometer = $odometerService->getLatestOdometer($sourceVehicle);
            if ($latestSourceOdometer !== null && $fromOdometer < $latestSourceOdometer) {
                $validator->errors()->add(
                    'from_odometer',
                    "Source odometer ({$fromOdometer}) cannot be lower than the latest known odometer for source vehicle ({$latestSourceOdometer})."
                );
            }
        }

        // Validate to_odometer against destination vehicle latest odometer
        $destinationVehicle = $this->movementVehicle($movement->to_location_type, $movement->to_location_id);
        if ($toOdometer !== null && $destinationVehicle) {
            $latestDestinationOdometer = $odometerService->getLatestOdometer($destinationVehicle);
            if ($latestDestinationOdometer !== null && $toOdometer < $latestDestinationOdometer) {
                $validator->errors()->add(
                    'to_odometer',
                    "Destination odometer ({$toOdometer}) cannot be lower than the latest known odometer for destination vehicle ({$latestDestinationOdometer})."
                );
            }
        }

        // For same vehicle position change, allow same odometer value
        if ($fromOdometer !== null && $toOdometer !== null) {
            $sourceVehicleId = $sourceVehicle?->id;
            $destinationVehicleId = $destinationVehicle?->id;

            if ($sourceVehicleId === $destinationVehicleId && $sourceVehicleId !== null) {
                // Same vehicle - allow equal values
                return;
            }

            // Different vehicles - to_odometer should generally be >= from_odometer
            // but we don't enforce this strictly as vehicles may have different odometers
        }
    }

    private function movementVehicle(?TyreLocationType $locationType, ?int $locationId): ?Vehicle
    {
        if (! $locationId || ! in_array($locationType, [TyreLocationType::PowerVehicle, TyreLocationType::Trailer], true)) {
            return null;
        }

        return Vehicle::query()->find($locationId);
    }

    public function messages(): array
    {
        return [
            'from_odometer.integer' => 'Source odometer must be a valid number.',
            'from_odometer.min' => 'Source odometer cannot be negative.',
            'to_odometer.integer' => 'Destination odometer must be a valid number.',
            'to_odometer.min' => 'Destination odometer cannot be negative.',
        ];
    }
}
