<?php

namespace App\Models;

use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Support\TyrePositionFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Tyre extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tyre_code',
        'serial_number',
        'brand_id',
        'size_id',
        'pattern',
        'supplier',
        'purchase_date',
        'purchase_price',
        'invoice_number',
        'initial_tread_depth',
        'current_tread_depth',
        'source',
        'current_location_type',
        'current_location_id',
        'current_position_code',
        'status',
        'qr_code_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'source' => TyreSource::class,
            'current_location_type' => TyreLocationType::class,
            'status' => TyreStatus::class,
            'purchase_date' => 'date',
            'purchase_price' => 'decimal:2',
            'initial_tread_depth' => 'decimal:2',
            'current_tread_depth' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(TyreBrand::class, 'brand_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(TyreSize::class, 'size_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TyreAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(TyreAssignment::class)->where('status', 'active');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(TyreMovement::class);
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(TyreDisposal::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(TyreInspection::class);
    }

    public function baseline(): HasOne
    {
        return $this->hasOne(TyreBaseline::class);
    }

    public function isDisposed(): bool
    {
        return $this->status === TyreStatus::Disposed;
    }

    public function canBeMoved(): bool
    {
        return ! $this->isDisposed()
            && ! in_array($this->status, [TyreStatus::Disposed], true);
    }

    public function totalKmUsed(): int
    {
        return (int) $this->assignments()->whereNotNull('km_used')->sum('km_used');
    }

    public function costPerKm(): ?float
    {
        $totalKm = $this->totalKmUsed();

        if ($totalKm <= 0 || ! $this->purchase_price) {
            return null;
        }

        return round((float) $this->purchase_price / $totalKm, 4);
    }

    public function currentVehiclePlateDisplay(): string
    {
        return $this->currentVehicleForDisplay()?->displayCodeWithPlate() ?? '-';
    }

    public function currentPositionDisplay(): string
    {
        return TyrePositionFormatter::display($this->current_position_code);
    }

    protected function currentVehicleForDisplay(): ?Vehicle
    {
        $assignment = $this->relationLoaded('activeAssignment')
            ? $this->activeAssignment
            : $this->activeAssignment()->with('vehicle')->first();

        if ($assignment?->vehicle) {
            return $assignment->vehicle;
        }

        if (! $this->current_location_id) {
            return null;
        }

        if (! in_array($this->current_location_type, [TyreLocationType::PowerVehicle, TyreLocationType::Trailer], true)) {
            return null;
        }

        return Vehicle::query()->find($this->current_location_id);
    }
}
