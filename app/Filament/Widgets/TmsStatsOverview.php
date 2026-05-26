<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\PendingApprovals;
use App\Filament\Resources\Tyres\TyreResource;
use App\Services\TyreReportService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TmsStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return [
            'default' => 2,
            'sm' => 3,
            'lg' => 4,
            'xl' => 4,
        ];
    }

    protected function getStats(): array
    {
        $stats = app(TyreReportService::class)->dashboardStats();

        return [
            Stat::make('Total Tyres', $stats['total_tyres'])
                ->icon('heroicon-o-circle-stack')
                ->url(TyreResource::getUrl('index')),
            Stat::make('Active on Fleet', $stats['active_tyres'])
                ->color('success'),
            Stat::make('In Store', $stats['in_store'])
                ->color('info'),
            Stat::make('Pending Approvals', $stats['pending_movements'])
                ->description('Movements awaiting completion')
                ->color('warning')
                ->url(PendingApprovals::getUrl()),
            Stat::make('Pending Registration', $stats['pending_registration'])
                ->color('gray')
                ->url(TyreResource::getUrl('index')),
            Stat::make('Power Units', $stats['power_vehicles']),
            Stat::make('Trailers', $stats['trailers']),
        ];
    }
}
