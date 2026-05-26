<?php

namespace App\Filament\Resources\TyreMovements\Pages;

use App\Filament\Concerns\InteractsWithVoucherWorkflow;
use App\Filament\Resources\TyreMovements\TyreMovementResource;
use App\Models\TyreMovement;
use Filament\Resources\Pages\EditRecord;

/** @property-read TyreMovement $record */
class EditTyreMovement extends EditRecord
{
    use InteractsWithVoucherWorkflow;

    protected static string $resource = TyreMovementResource::class;

    protected function getHeaderActions(): array
    {
        return $this->voucherWorkflowHeaderActions(TyreMovement::class, 'vouchers.movement.pdf');
    }
}
