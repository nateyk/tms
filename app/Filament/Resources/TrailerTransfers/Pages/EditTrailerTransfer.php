<?php

namespace App\Filament\Resources\TrailerTransfers\Pages;

use App\Filament\Concerns\InteractsWithVoucherWorkflow;
use App\Filament\Resources\TrailerTransfers\TrailerTransferResource;
use App\Models\TrailerTransfer;
use Filament\Resources\Pages\EditRecord;

/** @property-read TrailerTransfer $record */
class EditTrailerTransfer extends EditRecord
{
    use InteractsWithVoucherWorkflow;

    protected static string $resource = TrailerTransferResource::class;

    protected function getHeaderActions(): array
    {
        return $this->voucherWorkflowHeaderActions(TrailerTransfer::class, 'vouchers.trailer-transfer.pdf');
    }
}
