<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'code',
        'name',
        'store_id',
        'type',
        'address',
        'status',
        'notes',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'current_location_id');
    }
}
