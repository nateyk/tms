<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    protected $fillable = [
        'approval_request_id',
        'step_order',
        'step_name',
        'status',
        'acted_by',
        'acted_at',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'acted_at' => 'datetime',
            'step_order' => 'integer',
        ];
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function actedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
