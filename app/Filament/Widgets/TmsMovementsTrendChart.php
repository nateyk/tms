<?php

namespace App\Filament\Widgets;

use App\Services\TyreReportService;
use Filament\Widgets\ChartWidget;

class TmsMovementsTrendChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Completed Movements (8 weeks)';

    protected ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $trend = app(TyreReportService::class)->completedMovementsTrend();

        return [
            'datasets' => [
                [
                    'label' => 'Movements',
                    'data' => $trend['data'],
                    'backgroundColor' => '#d97706',
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }
}
