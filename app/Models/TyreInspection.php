<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TyreInspection extends Model
{
    protected $fillable = [
        'tyre_id',
        'vehicle_id',
        'position_code',
        'inspection_date',
        'tread_depth',
        'pressure',
        'audited_remaining_percentage',
        'calculated_remaining_percentage_at_audit',
        'audit_odometer',
        'variance_percentage',
        'condition',
        'inspector',
        'inspected_by',
        'audited_by',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'tread_depth' => 'decimal:2',
            'pressure' => 'decimal:2',
            'audited_remaining_percentage' => 'decimal:2',
            'calculated_remaining_percentage_at_audit' => 'decimal:2',
            'audit_odometer' => 'integer',
            'variance_percentage' => 'decimal:2',
        ];
    }

    public function tyre(): BelongsTo
    {
        return $this->belongsTo(Tyre::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function auditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audited_by');
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
