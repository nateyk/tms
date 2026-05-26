<?php

namespace App\Models;

use App\Enums\ApprovalRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends Model
{
    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'request_type',
        'status',
        'requested_by',
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
            'status' => ApprovalRequestStatus::class,
            'submitted_at' => 'datetime',
            'checked_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class);
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
