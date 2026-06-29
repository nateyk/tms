<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'status',
        'is_default',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function tyres(): HasMany
    {
        return $this->hasMany(Tyre::class, 'current_location_id')
            ->where('current_location_type', 'store');
    }
}
