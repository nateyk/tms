<?php

namespace App\Enums;

enum VoucherStatus: string
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
            self::Cancelled => 'Voided',
            self::Completed => 'Completed',
        };
    }

    public function isPending(): bool
    {
        return in_array($this, [self::Draft, self::Submitted, self::Checked, self::Approved], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Rejected, self::Cancelled, self::Completed], true);
    }
}
