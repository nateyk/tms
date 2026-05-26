<?php

namespace App\Filament\Resources\TrailerTransfers\Pages;

use App\Filament\Resources\TrailerTransfers\TrailerTransferResource;
use App\Services\TrailerTransferService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTrailerTransfer extends CreateRecord
{
    protected static string $resource = TrailerTransferResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(TrailerTransferService::class)->createDraft($data, (int) auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
