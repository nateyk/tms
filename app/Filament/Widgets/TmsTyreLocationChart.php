<?php

namespace App\Filament\Widgets;

use App\Services\TyreReportService;
use Filament\Widgets\ChartWidget;

class TmsTyreLocationChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Tyres by Location';

    protected ?string $maxHeight = '210px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                ],
                'y' => [
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        $chart = app(TyreReportService::class)->tyresByLocationChart();

        return [
            'datasets' => [
                [
                    'label' => 'Tyres',
                    'data' => $chart['data'],
                    'backgroundColor' => ['#2563eb', '#0f9f8f', '#0891b2', '#64748b'],
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $chart['labels'],
        ];
    }
}
