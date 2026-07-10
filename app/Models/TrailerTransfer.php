<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class TrailerTransfer extends Model
{
    use LogsActivity, SoftDeletes;

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

    public function checkedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function displayNumber(): string
    {
        if (preg_match('/^([A-Z]+)-(\d{4})(\d{2})(\d{2})-(\d+)$/', $this->transfer_no, $matches)) {
            return sprintf(
                '%s-%s%s%s-%03d',
                $matches[1],
                substr($matches[2], -2),
                $matches[3],
                $matches[4],
                (int) $matches[5],
            );
        }

        return $this->transfer_no;
    }
}
