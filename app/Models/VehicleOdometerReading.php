<?php

namespace App\Models;

use App\Enums\OdometerReadingSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleOdometerReading extends Model
{
    protected $fillable = [
        'vehicle_id',
        'odometer',
        'reading_date',
        'source',
        'source_id',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'odometer' => 'integer',
            'reading_date' => 'date',
            'source' => OdometerReadingSource::class,
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('reading_date')->orderByDesc('odometer')->orderByDesc('created_at');
    }

    public function scopeLatestReading($query)
    {
        return $query->orderByDesc('reading_date')->orderByDesc('odometer')->orderByDesc('created_at');
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }
}
