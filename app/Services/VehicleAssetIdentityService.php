<?php

namespace App\Services;

use App\Enums\AssetType;
use App\Models\Vehicle;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VehicleAssetIdentityService
{
    /**
     * @return array<string, string>
     */
    public function uniqueIdentityFields(): array
    {
        return [
            'vehicle_code' => 'Vehicle code',
            'plate_number' => 'Plate number',
            'chassis_number' => 'Chassis number',
            'engine_number' => 'Engine number',
        ];
    }

    public function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Str::upper($value);
    }

    public function assertUnique(Vehicle $vehicle): void
    {
        foreach ($this->uniqueIdentityFields() as $field => $label) {
            $value = $this->normalize($vehicle->{$field});

            if ($value === null) {
                continue;
            }

            $duplicate = Vehicle::query()
                ->withTrashed()
                ->whereRaw('upper('.$field.') = ?', [$value])
                ->when($vehicle->exists, fn ($query) => $query->whereKeyNot($vehicle->getKey()))
                ->first();

            if (! $duplicate) {
                continue;
            }

            $assetType = $this->assetTypeLabel($duplicate);

            throw ValidationException::withMessages([
                $field => "{$label} already exists in {$assetType}. Please use a unique value.",
            ]);
        }
    }

    /**
     * @return array<string, array<string, list<array{id: int, vehicle_code: string, plate_number: ?string, asset_type: string}>>>
     */
    public function duplicateReport(): array
    {
        $vehicles = Vehicle::query()
            ->withTrashed()
            ->select(['id', 'vehicle_code', 'plate_number', 'chassis_number', 'engine_number', 'asset_type'])
            ->orderBy('id')
            ->get();

        $report = [];

        foreach ($this->uniqueIdentityFields() as $field => $label) {
            $groups = [];

            foreach ($vehicles as $vehicle) {
                $value = $this->normalize($vehicle->{$field});

                if ($value === null) {
                    continue;
                }

                $groups[$value][] = [
                    'id' => (int) $vehicle->id,
                    'vehicle_code' => (string) $vehicle->vehicle_code,
                    'plate_number' => $vehicle->plate_number,
                    'asset_type' => $this->assetTypeLabel($vehicle),
                ];
            }

            $duplicates = array_filter($groups, fn (array $records): bool => count($records) > 1);

            if ($duplicates !== []) {
                $report[$label] = $duplicates;
            }
        }

        return $report;
    }

    public function assetTypeLabel(Vehicle $vehicle): string
    {
        $assetType = $vehicle->asset_type;

        if ($assetType instanceof AssetType) {
            return $assetType->label();
        }

        return Str::headline((string) $assetType);
    }
}
