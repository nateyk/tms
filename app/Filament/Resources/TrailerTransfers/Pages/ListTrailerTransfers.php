<?php

namespace App\Filament\Resources\TrailerTransfers\Pages;

use App\Filament\Resources\TrailerTransfers\TrailerTransferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrailerTransfers extends ListRecords
{
    protected static string $resource = TrailerTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
