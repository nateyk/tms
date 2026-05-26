<?php

namespace App\Filament\Resources\Tyres\Pages;

use App\Filament\Resources\Tyres\TyreResource;
use App\Services\TyreRegistrationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTyre extends CreateRecord
{
    protected static string $resource = TyreResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(TyreRegistrationService::class)->register($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
