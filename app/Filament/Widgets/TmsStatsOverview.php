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
            '2xl' => 4,
        ];
    }

    protected function getStats(): array
    {
        $stats = app(TyreReportService::class)->dashboardStats();

        return [
            Stat::make('Total Tyres', $stats['total_tyres'])
                ->icon('heroicon-o-circle-stack')
                ->color('primary')
                ->url(TyreResource::getUrl('index')),
            Stat::make('Active on Fleet', $stats['active_tyres'])
                ->icon('heroicon-o-truck')
                ->color('success'),
            Stat::make('In Store', $stats['in_store'])
                ->icon('heroicon-o-building-storefront')
                ->color('info'),
            Stat::make('Pending Approvals', $stats['pending_movements'])
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->url(PendingApprovals::getUrl()),
            Stat::make('Pending Registration', $stats['pending_registration'])
                ->icon('heroicon-o-document-plus')
                ->color('gray')
                ->url(TyreResource::getUrl('index')),
            Stat::make('Power Units', $stats['power_vehicles'])
                ->icon('heroicon-o-truck'),
            Stat::make('Trailers', $stats['trailers'])
                ->icon('heroicon-o-rectangle-stack'),
        ];
    }
}
