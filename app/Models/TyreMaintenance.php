<?php

namespace App\Models;

use App\Enums\AssignmentAssetType;
use App\Enums\MaintenanceProblemType;
use App\Enums\MaintenanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class TyreMaintenance extends Model
{
    use LogsActivity;

    protected $table = 'tyre_maintenance';

    protected $fillable = [
        'maintenance_no',
        'tyre_id',
        'asset_type',
        'asset_id',
        'position_code',
        'problem_type',
        'action_taken',
        'cost',
        'technician',
        'maintenance_date',
        'next_inspection_date',
        'status',
        'prepared_by',
        'approved_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'asset_type' => AssignmentAssetType::class,
            'problem_type' => MaintenanceProblemType::class,
            'status' => MaintenanceStatus::class,
            'maintenance_date' => 'date',
            'next_inspection_date' => 'date',
            'cost' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function tyre(): BelongsTo
    {
        return $this->belongsTo(Tyre::class);
    }

    public function preparedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
