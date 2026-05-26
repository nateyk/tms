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
        ];
    }

    public function tyre(): BelongsTo
    {
        return $this->belongsTo(Tyre::class);
    }
}
