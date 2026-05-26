<?php

namespace App\Filament\Widgets;

use App\Services\TyreReportService;
use Filament\Widgets\ChartWidget;

class TmsFleetUtilizationChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Fleet Position Fill';

    protected ?string $description = 'Active tyre positions vs empty slots on active vehicles';

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
        $utilization = app(TyreReportService::class)->fleetPositionUtilization();

        return [
            'datasets' => [
                [
                    'data' => [$utilization['filled'], $utilization['empty']],
                    'backgroundColor' => ['#16a34a', '#e5e7eb'],
                ],
            ],
            'labels' => ['Filled', 'Empty'],
        ];
    }
}
