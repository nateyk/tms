<?php

namespace App\Models;

use App\Enums\MovementType;
use App\Enums\TyreLocationType;
use App\Enums\VoucherStatus;
use App\Support\TyrePositionFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class TyreMovement extends Model
{
    use LogsActivity;

    protected $fillable = [
        'movement_no',
        'movement_type',
        'tyre_id',
        'from_location_type',
        'from_location_id',
        'from_position_code',
        'from_odometer',
        'to_location_type',
        'to_location_id',
        'to_position_code',
        'to_odometer',
        'movement_date',
        'reason',
        'status',
        'prepared_by',
        'checked_by',
        'approved_by',
        'submitted_at',
        'checked_at',
        'approved_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => MovementType::class,
            'from_location_type' => TyreLocationType::class,
            'to_location_type' => TyreLocationType::class,
            'status' => VoucherStatus::class,
            'movement_date' => 'date',
            'submitted_at' => 'datetime',
            'checked_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'from_odometer' => 'integer',
            'to_odometer' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function tyre(): BelongsTo
    {
        return $this->belongsTo(Tyre::class);
    }

    public function preparedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function checkedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === VoucherStatus::Completed;
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function displayNumber(): string
    {
        if (preg_match('/^([A-Z]+)-(\d{4})(\d{2})(\d{2})-(\d+)$/', $this->movement_no, $matches)) {
            return sprintf(
                '%s-%s%s%s-%03d',
                $matches[1],
                substr($matches[2], -2),
                $matches[3],
                $matches[4],
                (int) $matches[5],
            );
        }

        return $this->movement_no;
    }

    public function fromLocationDisplay(): string
    {
        return $this->locationDisplay($this->from_location_type, $this->from_location_id);
    }

    public function toLocationDisplay(): string
    {
        return $this->locationDisplay($this->to_location_type, $this->to_location_id);
    }

    public function fromPositionDisplay(): string
    {
        return TyrePositionFormatter::display($this->from_position_code);
    }

    public function toPositionDisplay(): string
    {
        return TyrePositionFormatter::display($this->to_position_code);
    }

    protected function locationDisplay(?TyreLocationType $type, ?int $id): string
    {
        if (! $type || ! $id) {
            return '-';
        }

        return match ($type) {
            TyreLocationType::PowerVehicle, TyreLocationType::Trailer => Vehicle::query()->find($id)?->displayCodeWithPlate() ?? (string) $id,
            TyreLocationType::Store => $this->storeDisplay($id),
            default => (string) $id,
        };
    }

    protected function storeDisplay(int $id): string
    {
        $store = Store::query()->find($id);

        if (! $store) {
            return (string) $id;
        }

        return collect([$store->code, $store->name])
            ->filter()
            ->implode(' - ') ?: (string) $id;
    }
}
