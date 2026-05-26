<?php

namespace App\Filament\Resources\TyreMaintenances\Pages;

use App\Filament\Resources\TyreMaintenances\TyreMaintenanceResource;
use App\Services\TyreMaintenanceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTyreMaintenance extends CreateRecord
{
    protected static string $resource = TyreMaintenanceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(TyreMaintenanceService::class)->createDraft($data, (int) auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
