<?php

namespace App\Filament\Resources\Vehicles\Pages;

use App\Enums\AssetType;
use App\Filament\Resources\Vehicles\VehicleResource;
use App\Livewire\CombinedVehicleTrailerMap;
use App\Livewire\VehicleTyreMap;
use App\Services\TyreMapWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;

class ViewVehicle extends ViewRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        $emptyCount = app(TyreMapWorkflowService::class)->emptyPositions($this->record)->count();

        return [
            Action::make('fill_gaps')
                ->label($emptyCount > 0 ? "Fill gaps ({$emptyCount})" : 'All positions filled')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->url(fn () => '#tyre-map-gaps-'.$this->record->getKey())
                ->visible($emptyCount > 0),
            Action::make('tyre_status_pdf')
                ->label('Tyre Status PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('vouchers.vehicle.tyre-status.pdf', $this->record))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $mapComponent = $this->record->asset_type === AssetType::PowerVehicle
            ? Livewire::make(CombinedVehicleTrailerMap::class, ['powerVehicleId' => $this->record->getKey()])
            : Livewire::make(VehicleTyreMap::class, ['vehicleId' => $this->record->getKey()]);

        return $schema
            ->components([
                $this->hasInfolist()
                    ? $this->getInfolistContentComponent()
                    : $this->getFormContentComponent(),
                $mapComponent->key('vehicle-tyre-map-'.$this->record->getKey()),
            ]);
    }
}
