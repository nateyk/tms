<?php

namespace App\Models;

use App\Enums\DisposalReason;
use App\Enums\TyreLocationType;
use App\Enums\VoucherStatus;
use App\Models\Concerns\LogsActivityCompatibility;
use App\Support\TyrePositionFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TyreDisposal extends Model
{
    use LogsActivityCompatibility;

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
        'submitted_at',
        'checked_at',
        'approved_at',
        'completed_at',
        'voided_by',
        'voided_at',
        'void_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_location_type' => TyreLocationType::class,
            'disposal_reason' => DisposalReason::class,
            'status' => VoucherStatus::class,
            'completed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'checked_at' => 'datetime',
            'approved_at' => 'datetime',
            'voided_at' => 'datetime',
            'estimated_scrap_value' => 'decimal:2',
            'sold_amount' => 'decimal:2',
            'final_km_used' => 'integer',
        ];
    }

    public function tyre(): BelongsTo
    {
        return $this->belongsTo(Tyre::class);
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

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function lastPositionDisplay(): string
    {
        return TyrePositionFormatter::display($this->last_position_code);
    }
}
