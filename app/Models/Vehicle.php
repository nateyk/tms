<?php

namespace App\Models;

use App\Enums\AssetType;
use App\Enums\AssignmentAssetType;
use App\Enums\CombinationStatus;
use App\Enums\TyreAssignmentStatus;
use App\Enums\VehicleStatus;
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
        ];
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

    public function attachedTrailer(): ?Vehicle
    {
        return $this->activeCombinationAsPower?->trailer;
    }

    public function attachedPower(): ?Vehicle
    {
        return $this->activeCombinationAsTrailer?->powerVehicle;
    }
}
