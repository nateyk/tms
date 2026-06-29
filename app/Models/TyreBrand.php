<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TyreBrand extends Model
{
    protected $fillable = ['name', 'code', 'status', 'notes'];

    public function tyres(): HasMany
    {
        return $this->hasMany(Tyre::class, 'brand_id');
    }
}
