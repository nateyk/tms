<?php

namespace App\Http\Requests\Tyres;

use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Enums\VoucherStatus;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreMovement;
use App\Models\Vehicle;
use App\Services\TyreMapWorkflowService;
use App\Services\VehicleOdometerService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTyreMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TyreMovement::class) ?? false;
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
        $vehicleTypes = $this->vehicleLocationValues();

        return [
            'tyre_id' => ['required', 'integer', 'exists:tyres,id'],
            'movement_date' => ['required', 'date'],
            'to_location_type' => ['required', Rule::in([
                TyreLocationType::Store->value,
                TyreLocationType::PowerVehicle->value,
                TyreLocationType::Trailer->value,
            ])],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tyre = Tyre::query()->with('activeAssignment')->find($this->integer('tyre_id'));
            if (! $tyre) {
                return;
            }

            if ($tyre->status === TyreStatus::Disposed) {
                $validator->errors()->add('tyre_id', 'Disposed tyres cannot be moved.');
            }

            if ($this->hasPendingMovement($tyre)) {
                $validator->errors()->add('tyre_id', 'This tyre already has a pending movement voucher.');
            }

            $toType = TyreLocationType::tryFrom((string) $this->input('to_location_type'));
            $toId = $this->integer('to_location_id') ?: null;
            $toPosition = (string) $this->input('to_position_code', '');

            if ($toType === TyreLocationType::Store) {
                if ($toId && ! Store::query()->whereKey($toId)->exists()) {
                    $validator->errors()->add('to_location_id', 'Select a valid destination store.');
                }
            }

            $sourceVehicle = $this->vehicleForLocation($tyre->current_location_type, $tyre->current_location_id);
            $destinationVehicle = $this->vehicleForLocation($toType, $toId);

            if ($this->isVehicleLocation($toType)) {
                if (! $destinationVehicle) {
                    $validator->errors()->add('to_location_id', 'Select a valid active destination vehicle.');
                } else {
                    if ($toType === TyreLocationType::Trailer && ! $destinationVehicle->attachedPower()) {
                        $validator->errors()->add('to_location_id', 'The trailer must be attached to a power vehicle before receiving a tyre.');
                    }

                    $this->validateDestinationPosition($validator, $destinationVehicle, $toPosition);
                }
            }

            if (
                $sourceVehicle
                && $destinationVehicle
                && $sourceVehicle->id === $destinationVehicle->id
                && filled($tyre->current_position_code)
                && $tyre->current_position_code === $toPosition
            ) {
                $validator->errors()->add('to_position_code', 'Source and destination position are the same. Choose a different empty position.');
            }

            if ($sourceVehicle && $this->positionRequiresOdometer($sourceVehicle, $tyre->current_position_code)) {
                if ($this->input('from_odometer') === null || $this->input('from_odometer') === '') {
                    $validator->errors()->add('from_odometer', 'Odometer out is required when removing a tyre from a running vehicle position.');
                }

                $installedOdometer = $tyre->activeAssignment?->installed_odometer;
                if ($installedOdometer !== null && $this->integer('from_odometer') < $installedOdometer) {
                    $validator->errors()->add('from_odometer', "Odometer out cannot be less than the installed odometer ({$installedOdometer}).");
                }
            }

            if ($destinationVehicle && $this->positionRequiresOdometer($destinationVehicle, $toPosition)) {
                if ($this->input('to_odometer') === null || $this->input('to_odometer') === '') {
                    $validator->errors()->add('to_odometer', 'Odometer in is required when mounting a tyre to a running vehicle position.');
                }

                $latest = app(VehicleOdometerService::class)->getLatestOdometer($destinationVehicle);
                if ($latest !== null && $this->integer('to_odometer') < $latest) {
                    $validator->errors()->add('to_odometer', "Odometer in cannot be less than the destination vehicle latest odometer ({$latest}).");
                }
            }
        });
    }

    /** @return list<string> */
    private function vehicleLocationValues(): array
    {
        return [
            TyreLocationType::PowerVehicle->value,
            TyreLocationType::Trailer->value,
        ];
    }

    private function isVehicleLocation(?TyreLocationType $type): bool
    {
        return in_array($type, [TyreLocationType::PowerVehicle, TyreLocationType::Trailer], true);
    }

    private function vehicleForLocation(?TyreLocationType $type, ?int $id): ?Vehicle
    {
        if (! $id || ! $this->isVehicleLocation($type)) {
            return null;
        }

        return Vehicle::query()
            ->whereKey($id)
            ->where('status', 'active')
            ->when(
                $type === TyreLocationType::PowerVehicle,
                fn ($query) => $query->whereIn('asset_type', ['power_vehicle', 'rigid_truck'])
            )
            ->when(
                $type === TyreLocationType::Trailer,
                fn ($query) => $query->where('asset_type', 'trailer')
            )
            ->first();
    }

    private function validateDestinationPosition(Validator $validator, Vehicle $vehicle, string $positionCode): void
    {
        if ($positionCode === '') {
            return;
        }

        $mapWorkflow = app(TyreMapWorkflowService::class);
        $positions = collect($mapWorkflow->positionStatusForVehicle($vehicle));
        $position = $positions->first(fn (array $option): bool => in_array($positionCode, [
            $option['code'],
            $option['display_code'],
        ], true));

        if (! $position) {
            $validator->errors()->add('to_position_code', 'Select a valid position configured for the destination vehicle.');
            return;
        }

        if (! $position['is_empty']) {
            $validator->errors()->add('to_position_code', 'This position already has a tyre. Create a swap movement or choose an empty position.');
        }
    }

    private function positionRequiresOdometer(Vehicle $vehicle, ?string $positionCode): bool
    {
        if (! $positionCode) {
            return false;
        }

        return ! app(TyreMapWorkflowService::class)->isSparePositionForVehicle($vehicle, $positionCode);
    }

    private function hasPendingMovement(Tyre $tyre): bool
    {
        return TyreMovement::query()
            ->where('tyre_id', $tyre->id)
            ->whereIn('status', [
                VoucherStatus::Draft,
                VoucherStatus::Submitted,
                VoucherStatus::Checked,
                VoucherStatus::Approved,
            ])
            ->exists();
    }
}
