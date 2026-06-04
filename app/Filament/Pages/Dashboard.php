<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Fleet Tyre Operations';

    public function getHeading(): string
    {
        return 'Fleet Tyre Operations';
    }

    public function getSubheading(): ?string
    {
        return 'Monitor tyre inventory, fleet fitment, movement activity, and approvals from one control surface.';
    }

    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }
}
