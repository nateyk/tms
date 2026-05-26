<?php

namespace App\Filament\Resources\Tyres\Pages;

use App\Enums\TyreStatus;
use App\Filament\Resources\Tyres\TyreResource;
use App\Models\Tyre;
use App\Services\TyreQrCodeService;
use App\Services\TyreRegistrationService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/** @property-read Tyre $record */
class ViewTyre extends ViewRecord
{
    protected static string $resource = TyreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve_registration')
                ->label('Approve Registration')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->status === TyreStatus::PendingApproval)
                ->authorize(fn () => tms_user()?->can('tyre.approve') ?? false)
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        app(TyreRegistrationService::class)->approve($this->record, (int) auth()->id());
                        Notification::make()->title('Tyre approved and QR generated.')->success()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Approval failed')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('regenerate_qr')
                ->label('Regenerate QR')
                ->icon('heroicon-o-qr-code')
                ->visible(fn () => $this->record->status !== TyreStatus::PendingApproval)
                ->action(function () {
                    app(TyreQrCodeService::class)->generateForTyre($this->record);
                    Notification::make()->title('QR code regenerated.')->success()->send();
                }),

            Action::make('qr_scan')
                ->label('QR Profile')
                ->icon('heroicon-o-qr-code')
                ->url(fn () => route('tyres.scan', $this->record->tyre_code))
                ->openUrlInNewTab(),
            Action::make('registration_pdf')
                ->label('Registration PDF')
                ->url(fn () => route('vouchers.tyre.registration.pdf', $this->record))
                ->openUrlInNewTab(),
            Action::make('history_pdf')
                ->label('History PDF')
                ->url(fn () => route('vouchers.tyre.history.pdf', $this->record))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
