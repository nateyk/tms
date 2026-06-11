<?php

namespace App\Filament\Concerns;

use App\Enums\MaintenanceStatus;
use App\Exceptions\TyreBusinessException;
use App\Models\TyreMaintenance;
use App\Services\TyreMaintenanceService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

/** @mixin EditRecord */
trait InteractsWithMaintenanceWorkflow
{
    use ResolvesFilamentRecord;

    /** @return array<int, Action|DeleteAction> */
    protected function maintenanceWorkflowHeaderActions(string $pdfRouteName = 'vouchers.maintenance.pdf'): array
    {
        $service = app(TyreMaintenanceService::class);

        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route($pdfRouteName, $this->filamentRecord(TyreMaintenance::class)))
                ->openUrlInNewTab(),

            Action::make('submit')
                ->label('Submit')
                ->color('info')
                ->visible(fn () => $this->maintenanceHasStatus(MaintenanceStatus::Draft))
                ->authorize(fn () => $this->userCan('maintenance.create'))
                ->requiresConfirmation()
                ->action(fn () => $this->handleMaintenanceStep(
                    fn () => $service->submit($this->filamentRecord(TyreMaintenance::class)),
                    'Maintenance submitted.'
                )),

            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->visible(fn () => $this->maintenanceHasStatus(MaintenanceStatus::Submitted))
                ->authorize(fn () => $this->userCan('maintenance.approve'))
                ->requiresConfirmation()
                ->action(fn () => $this->handleMaintenanceStep(
                    fn () => $service->approve($this->filamentRecord(TyreMaintenance::class), (int) auth()->id()),
                    'Maintenance approved.'
                )),

            Action::make('start')
                ->label('Start Work')
                ->color('warning')
                ->visible(fn () => $this->maintenanceHasStatus(MaintenanceStatus::Approved))
                ->authorize(fn () => $this->userCan('maintenance.complete'))
                ->requiresConfirmation()
                ->action(fn () => $this->handleMaintenanceStep(
                    fn () => $service->start($this->filamentRecord(TyreMaintenance::class)),
                    'Maintenance started.'
                )),

            Action::make('complete')
                ->label('Complete')
                ->color('primary')
                ->visible(fn () => $this->maintenanceHasStatus(MaintenanceStatus::InProgress))
                ->authorize(fn () => $this->userCan('maintenance.complete'))
                ->requiresConfirmation()
                ->action(fn () => $this->handleMaintenanceStep(
                    fn () => $service->complete($this->filamentRecord(TyreMaintenance::class), (int) auth()->id()),
                    'Maintenance completed.'
                )),

            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->visible(fn () => $this->maintenanceIsRejectable())
                ->authorize(fn () => $this->userCan('maintenance.reject'))
                ->schema([
                    Textarea::make('reason')->required(),
                ])
                ->action(function (array $data) use ($service) {
                    $this->handleMaintenanceStep(
                        fn () => $service->reject($this->filamentRecord(TyreMaintenance::class), $data['reason']),
                        'Maintenance rejected.'
                    );
                }),

            DeleteAction::make()
                ->visible(fn () => $this->maintenanceHasStatus(MaintenanceStatus::Draft)),
        ];
    }

    protected function maintenanceHasStatus(MaintenanceStatus $status): bool
    {
        $record = $this->record;

        return $record instanceof TyreMaintenance && $record->status === $status;
    }

    protected function maintenanceIsRejectable(): bool
    {
        $record = $this->record;

        return $record instanceof TyreMaintenance
            && in_array($record->status, [MaintenanceStatus::Submitted, MaintenanceStatus::Approved], true);
    }

    protected function handleMaintenanceStep(callable $callback, string $message): void
    {
        try {
            $record = $callback();

            Notification::make()->title($message)->success()->send();

            $this->record = $record;
            $this->fillForm();

            redirect($this->getResource()::getUrl('edit', ['record' => $record]));
        } catch (TyreBusinessException $e) {
            Notification::make()->title('Business rule violation')->body($e->getMessage())->danger()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Action failed')->body($e->getMessage())->danger()->send();
        }
    }
}
