<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TyreInspection extends Model
{
    protected $fillable = [
        'tyre_id',
        'inspection_date',
        'tread_depth',
        'pressure',
        'audited_remaining_percentage',
        'calculated_remaining_percentage_at_audit',
        'audit_odometer',
        'condition',
        'inspector',
        'inspected_by',
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
        ];
    }

    public function tyre(): BelongsTo
    {
        return $this->belongsTo(Tyre::class);
    }
}
