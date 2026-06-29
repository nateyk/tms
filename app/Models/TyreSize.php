<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TyreSize extends Model
{
    protected $fillable = ['size_label', 'code', 'status', 'notes'];

    public function tyres(): HasMany
    {
        return $this->hasMany(Tyre::class, 'size_id');
    }
}
