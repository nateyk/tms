<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TyreBaseline extends Model
{
    protected $fillable = [
        'tyre_id',
        'baseline_location_type',
        'baseline_location_id',
        'baseline_position_code',
        'baseline_odometer',
        'baseline_percentage',
        'expected_life_km',
        'baseline_date',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'baseline_odometer' => 'integer',
            'baseline_percentage' => 'decimal:2',
            'expected_life_km' => 'integer',
            'baseline_date' => 'date',
        ];
    }

    public function tyre(): BelongsTo
    {
        return $this->belongsTo(Tyre::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForTyre($query, $tyreId)
    {
        return $query->where('tyre_id', $tyreId);
    }
}
