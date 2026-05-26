<?php

namespace App\Filament\Resources\TyreDisposals\Pages;

use App\Filament\Resources\TyreDisposals\TyreDisposalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTyreDisposals extends ListRecords
{
    protected static string $resource = TyreDisposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
