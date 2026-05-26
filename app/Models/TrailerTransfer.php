<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class TrailerTransfer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'transfer_no',
        'trailer_vehicle_id',
        'from_power_vehicle_id',
        'to_power_vehicle_id',
        'transfer_date',
        'from_odometer',
        'to_odometer',
        'location_id',
        'reason',
        'status',
        'prepared_by',
        'checked_by',
        'approved_by',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => VoucherStatus::class,
            'transfer_date' => 'date',
            'completed_at' => 'datetime',
            'from_odometer' => 'integer',
            'to_odometer' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function trailer(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'trailer_vehicle_id');
    }

    public function fromPowerVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'from_power_vehicle_id');
    }

    public function toPowerVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'to_power_vehicle_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function preparedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
