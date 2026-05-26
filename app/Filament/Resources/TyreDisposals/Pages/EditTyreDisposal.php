<?php

namespace App\Filament\Resources\TyreDisposals\Pages;

use App\Filament\Concerns\InteractsWithVoucherWorkflow;
use App\Filament\Resources\TyreDisposals\TyreDisposalResource;
use App\Models\TyreDisposal;
use Filament\Resources\Pages\EditRecord;

/** @property-read TyreDisposal $record */
class EditTyreDisposal extends EditRecord
{
    use InteractsWithVoucherWorkflow;

    protected static string $resource = TyreDisposalResource::class;

    protected function getHeaderActions(): array
    {
        return $this->voucherWorkflowHeaderActions(TyreDisposal::class, 'vouchers.disposal.pdf');
    }
}
