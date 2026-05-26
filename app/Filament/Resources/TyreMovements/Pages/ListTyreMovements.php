<?php

namespace App\Filament\Resources\TyreMovements\Pages;

use App\Filament\Resources\TyreMovements\TyreMovementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTyreMovements extends ListRecords
{
    protected static string $resource = TyreMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
