<?php

namespace App\Models;

use App\Enums\AssignmentAssetType;
use App\Enums\TyreAssignmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TyreAssignment extends Model
{
    protected $fillable = [
        'tyre_id',
        'asset_type',
        'asset_id',
        'position_code',
        'installed_date',
        'installed_odometer',
        'removed_date',
        'removed_odometer',
        'km_used',
        'status',
        'installed_by',
        'removed_by',
        'movement_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'asset_type' => AssignmentAssetType::class,
            'status' => TyreAssignmentStatus::class,
            'installed_date' => 'date',
            'removed_date' => 'date',
            'installed_odometer' => 'integer',
            'removed_odometer' => 'integer',
            'km_used' => 'integer',
        ];
    }

    public function tyre(): BelongsTo
    {
        return $this->belongsTo(Tyre::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'asset_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(TyreMovement::class, 'movement_id');
    }

    public function installedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by');
    }

    public function removedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    public function calculateKmUsed(): ?int
    {
        if ($this->removed_odometer === null || $this->installed_odometer === null) {
            return $this->km_used;
        }

        return max(0, $this->removed_odometer - $this->installed_odometer);
    }
}
