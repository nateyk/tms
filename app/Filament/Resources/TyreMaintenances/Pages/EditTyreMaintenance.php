<?php

namespace App\Filament\Resources\TyreMaintenances\Pages;

use App\Filament\Concerns\InteractsWithMaintenanceWorkflow;
use App\Filament\Resources\TyreMaintenances\TyreMaintenanceResource;
use App\Models\TyreMaintenance;
use Filament\Resources\Pages\EditRecord;

/** @property-read TyreMaintenance $record */
class EditTyreMaintenance extends EditRecord
{
    use InteractsWithMaintenanceWorkflow;

    protected static string $resource = TyreMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return $this->maintenanceWorkflowHeaderActions();
    }
}
