<?php

namespace App\Models;

use App\Enums\AssetType;
use App\Enums\AssignmentAssetType;
use App\Enums\CombinationStatus;
use App\Enums\TyreAssignmentStatus;
use App\Enums\VehicleStatus;
use App\Services\VehicleAssetIdentityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vehicle_code',
        'plate_number',
        'chassis_number',
        'engine_number',
        'manufacture_year',
        'asset_type',
        'vehicle_type_id',
        'status',
        'current_location_id',
        'odometer',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'asset_type' => AssetType::class,
            'status' => VehicleStatus::class,
            'odometer' => 'integer',
            'manufacture_year' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Vehicle $vehicle): void {
            $identity = app(VehicleAssetIdentityService::class);

            foreach (array_keys($identity->uniqueIdentityFields()) as $field) {
                $vehicle->{$field} = $identity->normalize($vehicle->{$field});
            }

            $identity->assertUnique($vehicle);
        });
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    public function tyreAssignments(): HasMany
    {
        return $this->hasMany(TyreAssignment::class, 'asset_id');
    }

    public function activeTyreAssignments(): HasMany
    {
        return $this->tyreAssignments()->where('status', TyreAssignmentStatus::Active);
    }

    public function activeCombinationAsPower(): HasOne
    {
        return $this->hasOne(VehicleCombination::class, 'power_vehicle_id')
            ->where('status', CombinationStatus::Active);
    }

    public function activeCombinationAsTrailer(): HasOne
    {
        return $this->hasOne(VehicleCombination::class, 'trailer_vehicle_id')
            ->where('status', CombinationStatus::Active);
    }

    public function powerCombinations(): HasMany
    {
        return $this->hasMany(VehicleCombination::class, 'power_vehicle_id');
    }

    public function trailerCombinations(): HasMany
    {
        return $this->hasMany(VehicleCombination::class, 'trailer_vehicle_id');
    }

    public function isPowerVehicle(): bool
    {
        return $this->asset_type === AssetType::PowerVehicle;
    }

    public function isTrailer(): bool
    {
        return $this->asset_type === AssetType::Trailer;
    }

    public function assignmentAssetType(): ?AssignmentAssetType
    {
        return match ($this->asset_type) {
            AssetType::PowerVehicle => AssignmentAssetType::PowerVehicle,
            AssetType::Trailer => AssignmentAssetType::Trailer,
            default => null,
        };
    }

    public function displayCodeWithPlate(): string
    {
        if ($this->hasSameCodeAndPlate()) {
            return $this->plate_number ?: $this->vehicle_code ?: '-';
        }

        return collect([$this->vehicle_code, $this->plate_number])
            ->filter()
            ->implode(' / ') ?: '-';
    }

    public function vehicleCodeDisplay(): string
    {
        if ($this->hasSameCodeAndPlate()) {
            return 'Same as plate';
        }

        return $this->vehicle_code ?: '-';
    }

    public function hasSameCodeAndPlate(): bool
    {
        return filled($this->vehicle_code)
            && filled($this->plate_number)
            && $this->vehicle_code === $this->plate_number;
    }

    public function attachedTrailer(): ?Vehicle
    {
        return $this->activeCombinationAsPower?->trailer;
    }

    public function attachedPower(): ?Vehicle
    {
        return $this->activeCombinationAsTrailer?->powerVehicle;
    }
}
