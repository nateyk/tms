<?php

namespace App\Filament\Resources\VehicleTypes\Pages;

use App\Enums\PredefinedTyreLayout;
use App\Filament\Resources\VehicleTypes\VehicleTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVehicleType extends EditRecord
{
    protected static string $resource = VehicleTypeResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $preset = PredefinedTyreLayout::tryFromTyreCount((int) ($data['tyre_count'] ?? 0));
        $data['layout_preset'] = $preset?->value;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
