<?php

namespace App\Filament\Widgets;

use App\Services\TyreReportService;
use Filament\Widgets\ChartWidget;

class TmsTyreLocationChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Tyres by Location';

    protected ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];

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
                    'backgroundColor' => $chart['colors'],
                ],
            ],
            'labels' => $chart['labels'],
        ];
    }
}
