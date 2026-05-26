<?php

namespace App\Filament\Resources\TyreMovements\Pages;

use App\Filament\Resources\TyreMovements\TyreMovementResource;
use App\Services\TyreMapWorkflowService;
use App\Services\TyreMovementService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTyreMovement extends CreateRecord
{
    protected static string $resource = TyreMovementResource::class;

    public function mount(): void
    {
        parent::mount();

        $prefill = app(TyreMapWorkflowService::class)->prefilledMovementFromRequest();
        if ($prefill !== []) {
            $this->form->fill(array_merge($this->form->getState(), $prefill));
        }
    }

    public function getSubheading(): ?string
    {
        $vehicleId = (int) request()->query('vehicle_id', 0);
        $position = (string) request()->query('position', '');

        if ($vehicleId > 0 && $position !== '') {
            return "Filling gap: install a store tyre at position {$position}";
        }

        return parent::getSubheading();
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(TyreMovementService::class)->createDraft($data, (int) auth()->id());
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Movement draft created')
            ->body('Submit → Check → Approve → Complete to mount the tyre on the vehicle.')
            ->success();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
