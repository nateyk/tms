<?php

namespace App\Models;

use App\Enums\CombinationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleCombination extends Model
{
    protected $fillable = [
        'power_vehicle_id',
        'trailer_vehicle_id',
        'attached_date',
        'detached_date',
        'odometer_at_attach',
        'odometer_at_detach',
        'status',
        'attached_by',
        'detached_by',
        'approved_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attached_date' => 'date',
            'detached_date' => 'date',
            'status' => CombinationStatus::class,
            'odometer_at_attach' => 'integer',
            'odometer_at_detach' => 'integer',
        ];
    }

    public function powerVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'power_vehicle_id');
    }

    public function trailer(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'trailer_vehicle_id');
    }

    public function attachedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attached_by');
    }

    public function detachedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'detached_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
