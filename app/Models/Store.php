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

    protected static function booted(): void
    {
        static::creating(function (Store $store): void {
            if (! filled($store->code)) {
                $store->code = static::generateStoreCode();
            }
        });
    }

    public static function generateStoreCode(): string
    {
        $nextNumber = ((int) static::query()->max('id')) + 1;

        do {
            $code = sprintf('STR-%04d', $nextNumber++);
        } while (static::query()->where('code', $code)->exists());

        return $code;
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
