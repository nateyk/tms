<?php

namespace App\Filament\Resources\Tyres\Pages;

use App\Filament\Resources\Tyres\TyreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTyres extends ListRecords
{
    protected static string $resource = TyreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
