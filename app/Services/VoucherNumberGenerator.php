<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class VoucherNumberGenerator
{
    public function generate(string $prefix, Model $model, string $column = 'movement_no'): string
    {
        $compactDate = now()->format('ymd');
        $legacyDate = now()->format('Ymd');

        $last = $model->newQuery()
            ->where(function ($query) use ($column, $prefix, $compactDate, $legacyDate): void {
                $query
                    ->where($column, 'like', "{$prefix}-{$compactDate}-%")
                    ->orWhere($column, 'like', "{$prefix}-{$legacyDate}-%");
            })
            ->orderByDesc('id')
            ->value($column);

        $sequence = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('%s-%s-%03d', $prefix, $compactDate, $sequence);
    }
}
