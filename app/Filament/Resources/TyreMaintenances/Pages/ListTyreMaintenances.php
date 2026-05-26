<?php

namespace App\Filament\Resources\TyreMaintenances\Pages;

use App\Filament\Resources\TyreMaintenances\TyreMaintenanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTyreMaintenances extends ListRecords
{
    protected static string $resource = TyreMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
