<?php

namespace App\Filament\Concerns;

use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Models\TrailerTransfer;
use App\Models\TyreDisposal;
use App\Models\TyreMovement;
use App\Services\ApprovalService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/** @mixin EditRecord */
trait InteractsWithVoucherWorkflow
{
    use ResolvesFilamentRecord;

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<int, Action|DeleteAction>
     */
    protected function voucherWorkflowHeaderActions(string $modelClass, string $pdfRouteName): array
    {
        $approval = app(ApprovalService::class);

        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route($pdfRouteName, $this->filamentRecord($modelClass)))
                ->openUrlInNewTab(),

            Action::make('submit')
                ->label('Submit')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $this->voucherHasStatus($modelClass, VoucherStatus::Draft))
                ->authorize(fn () => $this->userCan('movement.create'))
                ->requiresConfirmation()
                ->action(fn () => $this->handleVoucherStep(
                    fn () => $approval->submit($this->filamentRecord($modelClass)),
                    'Voucher submitted.'
                )),

            Action::make('check')
                ->label('Check')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning')
                ->visible(fn () => $this->voucherHasStatus($modelClass, VoucherStatus::Submitted))
                ->authorize(fn () => $this->userCan('movement.check'))
                ->requiresConfirmation()
                ->action(fn () => $this->handleVoucherStep(
                    fn () => $approval->check($this->filamentRecord($modelClass)),
                    'Voucher checked.'
                )),

            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->voucherHasStatus($modelClass, VoucherStatus::Checked))
                ->authorize(fn () => $this->userCan('movement.approve'))
                ->requiresConfirmation()
                ->action(fn () => $this->handleVoucherStep(
                    fn () => $approval->approve($this->filamentRecord($modelClass)),
                    'Voucher approved.'
                )),

            Action::make('complete')
                ->label('Complete')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible(fn () => $this->voucherHasStatus($modelClass, VoucherStatus::Approved))
                ->authorize(fn () => $this->userCan('movement.approve'))
                ->requiresConfirmation()
                ->modalDescription('This will apply changes to tyre inventory. This cannot be undone.')
                ->action(fn () => $this->handleVoucherStep(
                    fn () => $this->completeVoucher($approval, $modelClass),
                    'Voucher completed successfully.'
                )),

            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->voucherIsRejectable($modelClass))
                ->authorize(fn () => $this->userCan('movement.reject'))
                ->schema([
                    Textarea::make('reason')->required(),
                ])
                ->action(function (array $data) use ($approval, $modelClass) {
                    $this->handleVoucherStep(
                        fn () => $approval->reject($this->filamentRecord($modelClass), $data['reason']),
                        'Voucher rejected.'
                    );
                }),

            DeleteAction::make()
                ->visible(fn () => $this->voucherHasStatus($modelClass, VoucherStatus::Draft)),
        ];
    }

    protected function voucherHasStatus(string $modelClass, VoucherStatus $status): bool
    {
        $record = $this->record;

        return $record instanceof $modelClass && $record->status === $status;
    }

    protected function voucherIsRejectable(string $modelClass): bool
    {
        $record = $this->record;

        if (! $record instanceof $modelClass) {
            return false;
        }

        $status = $record->status;

        if (! $status instanceof VoucherStatus) {
            return false;
        }

        return $status->isPending() && $status !== VoucherStatus::Draft;
    }

    protected function completeVoucher(ApprovalService $approval, string $modelClass): Model
    {
        return match ($modelClass) {
            TyreMovement::class => $approval->completeMovement($this->filamentRecord(TyreMovement::class)),
            TrailerTransfer::class => $approval->completeTrailerTransfer($this->filamentRecord(TrailerTransfer::class)),
            TyreDisposal::class => $approval->completeDisposal($this->filamentRecord(TyreDisposal::class)),
            default => throw new TyreBusinessException('Unsupported voucher type.'),
        };
    }

    protected function handleVoucherStep(callable $callback, string $message): void
    {
        try {
            $voucher = $callback();

            Notification::make()->title($message)->success()->send();

            $this->record = $voucher;
            $this->fillForm();

            redirect($this->getResource()::getUrl('edit', ['record' => $voucher]));
        } catch (TyreBusinessException $e) {
            Notification::make()->title('Business rule violation')->body($e->getMessage())->danger()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Action failed')->body($e->getMessage())->danger()->send();
        }
    }
}
