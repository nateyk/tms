<?php

namespace App\Services;

use App\Enums\AssignmentAssetType;
use App\Enums\CombinationStatus;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBrand;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Models\VehicleType;
use Illuminate\Support\Facades\DB;

class ExistingFleetTyreFitmentImporter
{
    /**
     * @param  list<array{sheet: string, positions: list<array{position: string, brand?: string|null, serial?: string|null, fitted_km?: int|null}>}>  $sheets
     * @return array{imported: int, mounted: int, spares: int, skipped: int, errors: list<string>}
     */
    public function import(array $sheets): array
    {
        $summary = [
            'imported' => 0,
            'mounted' => 0,
            'spares' => 0,
            'skipped' => 0,
            'errors' => [],
        ];
        $seenSerials = [];

        DB::transaction(function () use ($sheets, &$summary, &$seenSerials): void {
            foreach ($sheets as $sheet) {
                $power = $this->resolvePowerVehicle((string) $sheet['sheet']);

                if (! $power) {
                    $summary['errors'][] = "Sheet {$sheet['sheet']}: power vehicle was not found.";
                    $summary['skipped'] += count($sheet['positions']);

                    continue;
                }

                $trailer = $this->resolveActiveTrailer($power);

                foreach ($sheet['positions'] as $row) {
                    $position = $this->normalizePosition($row['position'] ?? '');
                    $serial = $this->normalizeSerial($row['serial'] ?? null);

                    if (! $this->isUsableSerial($serial)) {
                        $summary['skipped']++;

                        continue;
                    }

                    if (isset($seenSerials[$serial])) {
                        $summary['skipped']++;
                        $summary['errors'][] = "Sheet {$sheet['sheet']} {$position}: duplicate tyre serial {$serial} in import workbook.";

                        continue;
                    }

                    $seenSerials[$serial] = true;
                    $brand = $this->brand((string) ($row['brand'] ?? ''));
                    $tyre = $this->tyre($serial, $brand?->id, "Imported from existing fleet tyre workbook sheet {$sheet['sheet']} position {$position}.");

                    if ($this->isSparePosition($position)) {
                        $this->locateSpare($tyre, $power, $position);
                        $summary['imported']++;
                        $summary['spares']++;

                        continue;
                    }

                    $target = $this->assignmentTarget($power, $trailer, $position);

                    if (! $target) {
                        $summary['skipped']++;
                        $summary['errors'][] = "Sheet {$sheet['sheet']} {$position}: no matching vehicle tyre position.";

                        continue;
                    }

                    if (! $this->mountTyre($tyre, $target['asset_type'], $target['vehicle'], $target['position_code'], $row['fitted_km'] ?? null)) {
                        $summary['skipped']++;
                        $summary['errors'][] = "Sheet {$sheet['sheet']} {$position}: position or tyre already has an active assignment.";

                        continue;
                    }

                    $summary['imported']++;
                    $summary['mounted']++;
                }
            }
        });

        return $summary;
    }

    public function normalizeSerial(?string $serial): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', trim((string) $serial)));
    }

    public function normalizePosition(?string $position): string
    {
        return strtoupper(trim((string) $position));
    }

    public function isUsableSerial(string $serial): bool
    {
        return $serial !== ''
            && ! in_array($serial, ['0', 'NO', 'NONE', 'NONOBER', 'NONUMBER', 'SYSTEM.XML.XMLELEMENT'], true);
    }

    public function isSparePosition(string $position): bool
    {
        return in_array($position, ['W', 'X'], true);
    }

    private function resolvePowerVehicle(string $sheet): ?Vehicle
    {
        $suffix = preg_match('/^3-(\d+)$/', $sheet, $matches)
            ? 'A'.$matches[1]
            : 'A'.$sheet;

        return Vehicle::query()
            ->where('asset_type', 'power_vehicle')
            ->where('vehicle_code', 'like', '%'.$suffix)
            ->first();
    }

    private function resolveActiveTrailer(Vehicle $power): ?Vehicle
    {
        $combination = VehicleCombination::query()
            ->where('power_vehicle_id', $power->id)
            ->where('status', CombinationStatus::Active)
            ->first();

        $trailer = $combination?->trailer;

        return $trailer instanceof Vehicle ? $trailer : null;
    }

    /**
     * @return array{asset_type: AssignmentAssetType, vehicle: Vehicle, position_code: string}|null
     */
    private function assignmentTarget(Vehicle $power, ?Vehicle $trailer, string $position): ?array
    {
        if ($position >= 'A' && $position <= 'J') {
            $positionCode = $this->positionCodeForDisplay($power, $position);

            return $positionCode ? [
                'asset_type' => AssignmentAssetType::PowerVehicle,
                'vehicle' => $power,
                'position_code' => $positionCode,
            ] : null;
        }

        if (! $trailer) {
            return null;
        }

        $trailerDisplay = $this->trailerDisplayCode($position);

        if (! $trailerDisplay) {
            return null;
        }

        $positionCode = $this->positionCodeForDisplay($trailer, $trailerDisplay);

        return $positionCode ? [
            'asset_type' => AssignmentAssetType::Trailer,
            'vehicle' => $trailer,
            'position_code' => $positionCode,
        ] : null;
    }

    private function trailerDisplayCode(string $position): ?string
    {
        $map = [
            'K' => 'A',
            'L' => 'B',
            'M' => 'C',
            'N' => 'D',
            'O' => 'E',
            'P' => 'F',
            'Q' => 'G',
            'R' => 'H',
            'S' => 'I',
            'T' => 'J',
            'U' => 'K',
            'V' => 'L',
        ];

        return $map[$position] ?? null;
    }

    private function positionCodeForDisplay(Vehicle $vehicle, string $displayCode): ?string
    {
        $vehicleType = $vehicle->vehicleType;

        if (! $vehicleType instanceof VehicleType) {
            return null;
        }

        foreach ($vehicleType->positions() as $position) {
            if (($position['display_code'] ?? null) === $displayCode) {
                return (string) $position['code'];
            }
        }

        return null;
    }

    private function brand(string $name): ?TyreBrand
    {
        $name = strtoupper(trim($name));

        if ($name === '' || $name === '0') {
            return null;
        }

        return TyreBrand::query()->firstOrCreate(
            ['name' => $name],
            [
                'code' => substr((string) preg_replace('/[^A-Z0-9]+/', '', $name), 0, 12) ?: null,
                'status' => 'active',
                'notes' => 'Imported from existing fleet tyre workbook.',
            ]
        );
    }

    private function tyre(string $serial, ?int $brandId, string $notes): Tyre
    {
        return Tyre::query()->firstOrCreate(
            ['serial_number' => $serial],
            [
                'tyre_code' => $serial,
                'brand_id' => $brandId,
                'source' => TyreSource::ExistingVehicle,
                'current_location_type' => TyreLocationType::Store,
                'status' => TyreStatus::Available,
                'notes' => $notes,
            ]
        );
    }

    private function locateSpare(Tyre $tyre, Vehicle $power, string $position): void
    {
        if ($tyre->activeAssignment()->exists()) {
            return;
        }

        $tyre->update([
            'current_location_type' => TyreLocationType::PowerVehicle,
            'current_location_id' => $power->id,
            'current_position_code' => 'SPARE-'.$position,
            'status' => TyreStatus::Available,
        ]);
    }

    private function mountTyre(
        Tyre $tyre,
        AssignmentAssetType $assetType,
        Vehicle $vehicle,
        string $positionCode,
        ?int $installedOdometer
    ): bool {
        $activeForTyre = TyreAssignment::query()
            ->where('tyre_id', $tyre->id)
            ->where('status', TyreAssignmentStatus::Active)
            ->first();

        if ($activeForTyre) {
            return $activeForTyre->asset_id === $vehicle->id
                && $activeForTyre->position_code === $positionCode;
        }

        $activeForPosition = TyreAssignment::query()
            ->where('asset_type', $assetType)
            ->where('asset_id', $vehicle->id)
            ->where('position_code', $positionCode)
            ->where('status', TyreAssignmentStatus::Active)
            ->exists();

        if ($activeForPosition) {
            return false;
        }

        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => $assetType,
            'asset_id' => $vehicle->id,
            'position_code' => $positionCode,
            'installed_date' => now()->toDateString(),
            'installed_odometer' => $installedOdometer ?? $vehicle->odometer,
            'status' => TyreAssignmentStatus::Active,
            'notes' => 'Mounted from existing fleet tyre workbook import.',
        ]);

        $tyre->update([
            'current_location_type' => $assetType === AssignmentAssetType::Trailer
                ? TyreLocationType::Trailer
                : TyreLocationType::PowerVehicle,
            'current_location_id' => $vehicle->id,
            'current_position_code' => $positionCode,
            'status' => TyreStatus::Active,
        ]);

        return true;
    }
}
