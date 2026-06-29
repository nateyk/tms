<?php

namespace App\Models;

use App\Enums\DisposalReason;
use App\Enums\TyreLocationType;
use App\Enums\VoucherStatus;
use App\Support\TyrePositionFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class TyreDisposal extends Model
{
    use LogsActivity;

    protected $fillable = [
        'disposal_no',
        'tyre_id',
        'last_location_type',
        'last_location_id',
        'last_position_code',
        'final_km_used',
        'final_condition',
        'disposal_reason',
        'estimated_scrap_value',
        'sold_amount',
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
            'last_location_type' => TyreLocationType::class,
            'disposal_reason' => DisposalReason::class,
            'status' => VoucherStatus::class,
            'completed_at' => 'datetime',
            'estimated_scrap_value' => 'decimal:2',
            'sold_amount' => 'decimal:2',
            'final_km_used' => 'integer',
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

    public function lastPositionDisplay(): string
    {
        return TyrePositionFormatter::display($this->last_position_code);
    }
}
