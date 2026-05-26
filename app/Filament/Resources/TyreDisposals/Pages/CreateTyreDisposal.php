<?php

namespace App\Filament\Resources\TyreDisposals\Pages;

use App\Filament\Resources\TyreDisposals\TyreDisposalResource;
use App\Services\TyreDisposalService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTyreDisposal extends CreateRecord
{
    protected static string $resource = TyreDisposalResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(TyreDisposalService::class)->createDraft($data, (int) auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
