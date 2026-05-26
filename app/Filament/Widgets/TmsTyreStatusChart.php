<?php

namespace App\Filament\Widgets;

use App\Enums\TyreStatus;
use App\Models\Tyre;
use Filament\Widgets\ChartWidget;

class TmsTyreStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Tyres by Status';

    protected ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = Tyre::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];
        $colors = [];

        foreach (TyreStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);
            if ($count === 0) {
                continue;
            }
            $labels[] = $status->label();
            $data[] = $count;
            $colors[] = match ($status) {
                TyreStatus::Active => '#16a34a',
                TyreStatus::Available => '#2563eb',
                TyreStatus::Maintenance => '#ea580c',
                TyreStatus::Damaged => '#dc2626',
                TyreStatus::Disposed => '#1f2937',
                TyreStatus::PendingApproval => '#ca8a04',
            };
        }

        return [
            'datasets' => [
                [
                    'label' => 'Tyres',
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
