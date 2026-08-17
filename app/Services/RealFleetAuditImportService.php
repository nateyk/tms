<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AssetType;
use App\Enums\AssignmentAssetType;
use App\Enums\CombinationStatus;
use App\Enums\OdometerReadingSource;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Enums\VehicleStatus;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBaseline;
use App\Models\TyreBrand;
use App\Models\TyreInspection;
use App\Models\TyreSize;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Models\VehicleOdometerReading;
use App\Models\VehicleType;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class RealFleetAuditImportService
{
    private const EXPECTED_LIFE_KM = 80000;

    public function __construct(
        private readonly VehicleTyreLayoutBuilder $layoutBuilder,
    ) {}

    /**
     * @return array{fleets: int, vehicles: int, tyres: int, empty_positions: int}
     *
     * @throws JsonException
     */
    public function importFromFile(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Fleet audit manifest not found: {$path}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Unable to read fleet audit manifest: {$path}");
        }

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return $this->import($manifest);
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array{fleets: int, vehicles: int, tyres: int, empty_positions: int}
     */
    public function import(array $manifest): array
    {
        $this->validateManifest($manifest);

        /** @var list<array<string, mixed>> $fleets */
        $fleets = $manifest['fleets'];

        return DB::transaction(function () use ($fleets): array {
            $user = $this->importUser();
            $powerType = VehicleType::query()
                ->where('name', 'Heavy truck - 24 tyres (6 axles + W/X spares)')
                ->firstOrFail();
            $trailerType = $this->trailerType();
            $size = TyreSize::query()->where('size_label', '315/80R22.5')->firstOrFail();
            $tyreCount = 0;
            $emptyPositionCount = 0;

            foreach ($fleets as $fleet) {
                $power = $this->vehicle(
                    plate: (string) $fleet['power_plate'],
                    assetType: AssetType::PowerVehicle,
                    type: $powerType,
                    odometer: (int) $fleet['odometer'],
                    auditDate: (string) $fleet['audit_date'],
                    user: $user,
                );
                $trailer = $this->vehicle(
                    plate: (string) $fleet['trailer_plate'],
                    assetType: AssetType::Trailer,
                    type: $trailerType,
                    odometer: (int) $fleet['odometer'],
                    auditDate: (string) $fleet['audit_date'],
                    user: $user,
                );

                $this->combination($power, $trailer, (int) $fleet['odometer'], (string) $fleet['audit_date'], $user);
                $this->recordVehicleKm($power, (int) $fleet['odometer'], (string) $fleet['audit_date'], $user);
                $this->recordVehicleKm($trailer, (int) $fleet['odometer'], (string) $fleet['audit_date'], $user);

                /** @var list<array{position: string, brand: string, serial: string, percentage: int|float, remark: ?string}> $tyres */
                $tyres = $fleet['tyres'];
                $this->reconcilePositions($power, $trailer, $tyres, (int) $fleet['odometer'], (string) $fleet['audit_date'], $user);

                foreach ($tyres as $row) {
                    $this->importTyre($power, $trailer, $size, $user, $fleet, $row);
                    $tyreCount++;
                }

                $emptyPositionCount += count($fleet['empty_positions']);
            }

            return [
                'fleets' => count($fleets),
                'vehicles' => count($fleets) * 2,
                'tyres' => $tyreCount,
                'empty_positions' => $emptyPositionCount,
            ];
        });
    }

    /** @param array<string, mixed> $manifest */
    private function validateManifest(array $manifest): void
    {
        if (! isset($manifest['fleets']) || ! is_array($manifest['fleets'])) {
            throw new InvalidArgumentException('Fleet audit manifest must contain a fleets array.');
        }

        if ((int) ($manifest['fleet_count'] ?? -1) !== count($manifest['fleets'])) {
            throw new InvalidArgumentException('Fleet count does not match the manifest contents.');
        }

        $plates = [];
        $serials = [];
        $tyreCount = 0;

        foreach ($manifest['fleets'] as $index => $fleet) {
            if (! is_array($fleet)) {
                throw new InvalidArgumentException("Fleet entry {$index} is invalid.");
            }

            $label = (string) ($fleet['sheet'] ?? "#{$index}");
            $powerPlate = trim((string) ($fleet['power_plate'] ?? ''));
            $trailerPlate = trim((string) ($fleet['trailer_plate'] ?? ''));
            $odometer = (int) ($fleet['odometer'] ?? 0);
            $auditDate = (string) ($fleet['audit_date'] ?? '');
            $tyres = $fleet['tyres'] ?? null;
            $emptyPositions = $fleet['empty_positions'] ?? null;

            if ($powerPlate === '' || $trailerPlate === '' || $odometer <= 0 || ! is_array($tyres) || ! is_array($emptyPositions)) {
                throw new InvalidArgumentException("Fleet {$label} is missing required plate, odometer, tyre, or empty-position data.");
            }

            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $auditDate);
            if (! $date || $date->format('Y-m-d') !== $auditDate) {
                throw new InvalidArgumentException("Fleet {$label} has an invalid audit date.");
            }

            foreach ([$powerPlate, $trailerPlate] as $plate) {
                if (isset($plates[$plate])) {
                    throw new InvalidArgumentException("Vehicle plate {$plate} appears more than once.");
                }
                $plates[$plate] = true;
            }

            $positions = [];
            foreach ($tyres as $row) {
                if (! is_array($row)) {
                    throw new InvalidArgumentException("Fleet {$label} contains an invalid tyre row.");
                }

                $position = strtoupper(trim((string) ($row['position'] ?? '')));
                $serial = strtoupper(trim((string) ($row['serial'] ?? '')));
                $brand = trim((string) ($row['brand'] ?? ''));
                $percentage = $row['percentage'] ?? null;

                if (! in_array($position, range('A', 'X'), true) || $serial === '' || $brand === '' || ! is_numeric($percentage)) {
                    throw new InvalidArgumentException("Fleet {$label} has an incomplete tyre at position {$position}.");
                }
                if ((float) $percentage < 0 || (float) $percentage > 100) {
                    throw new InvalidArgumentException("Fleet {$label} has an invalid percentage at position {$position}.");
                }
                if (isset($positions[$position])) {
                    throw new InvalidArgumentException("Fleet {$label} repeats position {$position}.");
                }
                if (isset($serials[$serial])) {
                    throw new InvalidArgumentException("Tyre serial {$serial} appears more than once.");
                }

                $positions[$position] = true;
                $serials[$serial] = true;
                $tyreCount++;
            }

            foreach ($emptyPositions as $position) {
                $position = strtoupper(trim((string) $position));
                if (! in_array($position, range('A', 'X'), true) || isset($positions[$position])) {
                    throw new InvalidArgumentException("Fleet {$label} has an invalid empty position {$position}.");
                }
                $positions[$position] = true;
            }

            $accountedPositions = array_keys($positions);
            sort($accountedPositions);
            if ($accountedPositions !== range('A', 'X')) {
                $missing = array_values(array_diff(range('A', 'X'), $accountedPositions));
                throw new InvalidArgumentException("Fleet {$label} does not account for every A-X position: ".implode(', ', $missing));
            }
        }

        if ((int) ($manifest['tyre_count'] ?? -1) !== $tyreCount) {
            throw new InvalidArgumentException('Tyre count does not match the manifest contents.');
        }
    }

    private function importUser(): User
    {
        $user = User::query()->where('email', 'admin@menkem.com')->first()
            ?? User::query()->whereHas('roles', fn ($query) => $query->where('name', 'Super Admin'))->oldest('id')->first()
            ?? User::query()->oldest('id')->first();

        if (! $user) {
            throw new RuntimeException('At least one existing user is required to own imported audit records.');
        }

        return $user;
    }

    private function vehicle(
        string $plate,
        AssetType $assetType,
        VehicleType $type,
        int $odometer,
        string $auditDate,
        User $user,
    ): Vehicle {
        $vehicle = Vehicle::withTrashed()->where('plate_number', $plate)->first();

        if (! $vehicle) {
            $vehicle = new Vehicle(['plate_number' => $plate]);
        } elseif ($vehicle->trashed()) {
            $vehicle->restore();
        }

        $vehicle->forceFill([
            'asset_type' => $assetType,
            'vehicle_type_id' => $type->id,
            'status' => VehicleStatus::Active,
            'odometer' => $odometer,
            'odometer_last_updated_at' => $auditDate.' 00:00:00',
            'odometer_last_updated_by' => $user->id,
            'notes' => 'Imported from the canonical fleet tyre audit workbook.',
        ])->save();

        return $vehicle;
    }

    private function combination(Vehicle $power, Vehicle $trailer, int $odometer, string $auditDate, User $user): void
    {
        VehicleCombination::query()
            ->where('status', CombinationStatus::Active)
            ->where(function ($query) use ($power, $trailer): void {
                $query->where('power_vehicle_id', $power->id)
                    ->orWhere('trailer_vehicle_id', $trailer->id);
            })
            ->where(function ($query) use ($power, $trailer): void {
                $query->where('power_vehicle_id', '!=', $power->id)
                    ->orWhere('trailer_vehicle_id', '!=', $trailer->id);
            })
            ->update([
                'status' => CombinationStatus::Detached,
                'detached_date' => $auditDate,
                'odometer_at_detach' => $odometer,
                'detached_by' => $user->id,
            ]);

        VehicleCombination::query()->updateOrCreate(
            [
                'power_vehicle_id' => $power->id,
                'trailer_vehicle_id' => $trailer->id,
            ],
            [
                'attached_date' => $auditDate,
                'detached_date' => null,
                'odometer_at_attach' => $odometer,
                'odometer_at_detach' => null,
                'status' => CombinationStatus::Active,
                'attached_by' => $user->id,
                'detached_by' => null,
                'approved_by' => $user->id,
                'notes' => 'Imported active combination from the fleet tyre audit workbook.',
            ],
        );
    }

    private function recordVehicleKm(Vehicle $vehicle, int $odometer, string $auditDate, User $user): void
    {
        VehicleOdometerReading::query()->updateOrCreate(
            [
                'vehicle_id' => $vehicle->id,
                'odometer' => $odometer,
                'source' => OdometerReadingSource::Import,
            ],
            [
                'reading_date' => $auditDate,
                'recorded_by' => $user->id,
                'notes' => 'Imported from the canonical fleet tyre audit workbook.',
            ],
        );
    }

    /**
     * @param  list<array{position: string, brand: string, serial: string, percentage: int|float, remark: ?string}>  $rows
     */
    private function reconcilePositions(
        Vehicle $power,
        Vehicle $trailer,
        array $rows,
        int $odometer,
        string $auditDate,
        User $user,
    ): void {
        $wanted = collect($rows)->pluck('serial', 'position')->all();

        foreach (range('A', 'X') as $position) {
            $owner = $this->ownerForPosition($power, $trailer, $position);
            $assignment = TyreAssignment::query()
                ->where('asset_type', $owner->assignmentAssetType())
                ->where('asset_id', $owner->id)
                ->where('position_code', $position)
                ->where('status', TyreAssignmentStatus::Active)
                ->with('tyre')
                ->first();

            if ($assignment && (($wanted[$position] ?? null) !== $assignment->tyre?->serial_number)) {
                $this->removeAssignment($assignment, $odometer, $auditDate, $user);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $fleet
     * @param  array{position: string, brand: string, serial: string, percentage: int|float, remark: ?string}  $row
     */
    private function importTyre(
        Vehicle $power,
        Vehicle $trailer,
        TyreSize $size,
        User $user,
        array $fleet,
        array $row,
    ): void {
        $position = $row['position'];
        $percentage = (float) $row['percentage'];
        $odometer = (int) $fleet['odometer'];
        $auditDate = (string) $fleet['audit_date'];
        $owner = $this->ownerForPosition($power, $trailer, $position);
        $assignmentType = $owner->isTrailer() ? AssignmentAssetType::Trailer : AssignmentAssetType::PowerVehicle;
        $locationType = $owner->isTrailer() ? TyreLocationType::Trailer : TyreLocationType::PowerVehicle;
        $brand = $this->brand($row['brand']);
        $tyre = Tyre::withTrashed()->where('serial_number', $row['serial'])->first();

        if (! $tyre) {
            $tyre = new Tyre([
                'tyre_code' => $this->nextTyreCode(),
                'serial_number' => $row['serial'],
            ]);
        } elseif ($tyre->trashed()) {
            $tyre->restore();
        }

        foreach ($tyre->assignments()->where('status', TyreAssignmentStatus::Active)->get() as $activeAssignment) {
            if ((int) $activeAssignment->asset_id !== $owner->id || $activeAssignment->position_code !== $position) {
                $this->removeAssignment($activeAssignment, $odometer, $auditDate, $user);
            }
        }

        $positionAssignment = TyreAssignment::query()
            ->where('asset_type', $assignmentType)
            ->where('asset_id', $owner->id)
            ->where('position_code', $position)
            ->where('status', TyreAssignmentStatus::Active)
            ->first();

        if ($positionAssignment && (int) $positionAssignment->tyre_id !== (int) $tyre->id) {
            $this->removeAssignment($positionAssignment, $odometer, $auditDate, $user);
        }

        $notes = collect([
            'Imported from '.$fleet['sheet'].' in the canonical fleet tyre audit workbook.',
            $row['remark'] ?? null,
        ])->filter()->implode(' ');

        $tyre->forceFill([
            'brand_id' => $brand->id,
            'size_id' => $size->id,
            'pattern' => 'Imported fleet audit',
            'supplier' => 'Existing fleet fitment',
            'initial_tread_depth' => 20,
            'current_tread_depth' => $this->treadDepth($percentage),
            'source' => TyreSource::ExistingVehicle,
            'current_location_type' => $locationType,
            'current_location_id' => $owner->id,
            'current_position_code' => $position,
            'status' => TyreStatus::Active,
            'notes' => $notes,
        ])->save();

        TyreAssignment::query()->updateOrCreate(
            [
                'tyre_id' => $tyre->id,
                'status' => TyreAssignmentStatus::Active,
            ],
            [
                'asset_type' => $assignmentType,
                'asset_id' => $owner->id,
                'position_code' => $position,
                'installed_date' => $auditDate,
                'installed_odometer' => $odometer,
                'removed_date' => null,
                'removed_odometer' => null,
                'km_used' => null,
                'installed_by' => $user->id,
                'removed_by' => null,
                'notes' => 'Opening fitment imported from the canonical fleet tyre audit workbook.',
            ],
        );

        TyreBaseline::query()->updateOrCreate(
            ['tyre_id' => $tyre->id],
            [
                'baseline_location_type' => $locationType,
                'baseline_location_id' => $owner->id,
                'baseline_position_code' => $position,
                'baseline_odometer' => $odometer,
                'baseline_percentage' => $percentage,
                'expected_life_km' => self::EXPECTED_LIFE_KM,
                'baseline_date' => $auditDate,
                'created_by' => $user->id,
                'notes' => 'Opening baseline imported from the fleet tyre audit workbook.',
            ],
        );

        TyreInspection::query()->updateOrCreate(
            [
                'tyre_id' => $tyre->id,
                'inspection_date' => $auditDate,
                'audit_odometer' => $odometer,
            ],
            [
                'vehicle_id' => $owner->id,
                'position_code' => $position,
                'tread_depth' => $this->treadDepth($percentage),
                'audited_remaining_percentage' => $percentage,
                'calculated_remaining_percentage_at_audit' => $percentage,
                'variance_percentage' => 0,
                'condition' => $this->condition($percentage),
                'inspector' => 'Imported tyre audit',
                'inspected_by' => $user->id,
                'audited_by' => $user->id,
                'reason' => 'Opening fleet audit import',
                'notes' => $notes,
            ],
        );
    }

    private function removeAssignment(TyreAssignment $assignment, int $odometer, string $auditDate, User $user): void
    {
        $assignment->update([
            'status' => TyreAssignmentStatus::Removed,
            'removed_date' => $auditDate,
            'removed_odometer' => max($odometer, (int) ($assignment->installed_odometer ?? $odometer)),
            'km_used' => max(0, $odometer - (int) ($assignment->installed_odometer ?? $odometer)),
            'removed_by' => $user->id,
            'notes' => 'Closed while reconciling the canonical fleet tyre audit workbook.',
        ]);

        $tyre = $assignment->tyre;
        if ($tyre && ! $tyre->assignments()->where('status', TyreAssignmentStatus::Active)->exists()) {
            $tyre->update([
                'current_location_type' => TyreLocationType::Store,
                'current_location_id' => null,
                'current_position_code' => null,
                'status' => TyreStatus::Available,
            ]);
        }
    }

    private function ownerForPosition(Vehicle $power, Vehicle $trailer, string $position): Vehicle
    {
        return $position <= 'J' ? $power : $trailer;
    }

    private function brand(string $name): TyreBrand
    {
        $code = match ($name) {
            'Black Hawk' => 'BLK',
            'Triangle' => 'TRI',
            'DUPRO' => 'DUP',
            'Goodride' => 'GOO',
            'Sailun' => 'SAI',
            default => strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $name) ?: 'BRD', 0, 3)),
        };

        return TyreBrand::query()->updateOrCreate(
            ['name' => $name],
            ['code' => $code, 'status' => 'active'],
        );
    }

    private function trailerType(): VehicleType
    {
        $layout = $this->layoutBuilder->buildLayout(12, 3, 'T');
        foreach ($layout['positions'] as $index => &$position) {
            $code = chr(75 + $index);
            $position['code'] = $code;
            $position['display_code'] = $code;
            $position['label'] = 'Trailer position '.$code;
        }
        unset($position);

        $layout['positions'][] = [
            'code' => 'W', 'display_code' => 'W', 'legacy_code' => null,
            'label' => 'Trailer spare W', 'axle' => 4, 'side' => 'center',
            'dual' => 'single', 'x' => 360, 'y' => 346,
        ];
        $layout['positions'][] = [
            'code' => 'X', 'display_code' => 'X', 'legacy_code' => null,
            'label' => 'Trailer spare X', 'axle' => 4, 'side' => 'center',
            'dual' => 'single', 'x' => 520, 'y' => 346,
        ];

        return VehicleType::query()->updateOrCreate(
            ['name' => 'Attached trailer - 12 tyres + W/X spares'],
            [
                'asset_type' => AssetType::Trailer,
                'axle_count' => 3,
                'tyre_count' => 14,
                'layout_json' => $layout,
                'status' => 'active',
            ],
        );
    }

    private function nextTyreCode(): string
    {
        $next = ((int) Tyre::withTrashed()->max('id')) + 1;
        do {
            $code = sprintf('TYR-%04d', $next++);
        } while (Tyre::withTrashed()->where('tyre_code', $code)->exists());

        return $code;
    }

    private function treadDepth(float $percentage): float
    {
        return round(($percentage / 100) * 20, 2);
    }

    private function condition(float $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'Good',
            $percentage >= 50 => 'Watch',
            $percentage >= 30 => 'Low',
            default => 'End of Life',
        };
    }
}
