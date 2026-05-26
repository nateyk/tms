<?php

namespace App\Enums;

enum ApprovalRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Checked = 'checked';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Checked => 'Checked',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }
}
