<?php

namespace App\Models;

use App\Enums\AssetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    protected $fillable = [
        'name',
        'asset_type',
        'axle_count',
        'tyre_count',
        'layout_json',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'asset_type' => AssetType::class,
            'layout_json' => 'array',
            'axle_count' => 'integer',
            'tyre_count' => 'integer',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function positions(): array
    {
        return $this->layout_json['positions'] ?? [];
    }
}
