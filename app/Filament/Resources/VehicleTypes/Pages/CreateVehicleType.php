<?php

namespace App\Filament\Resources\VehicleTypes\Pages;

use App\Enums\PredefinedTyreLayout;
use App\Filament\Resources\VehicleTypes\VehicleTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicleType extends CreateRecord
{
    protected static string $resource = VehicleTypeResource::class;

    public function mount(): void
    {
        parent::mount();

        $preset = PredefinedTyreLayout::PowerUnit10;
        $layout = app(\App\Services\VehicleTyreLayoutBuilder::class)
            ->buildLayout($preset->tyreCount(), $preset->axleCount(), $preset->positionPrefix());

        $this->form->fill([
            'layout_preset' => $preset->value,
            'tyre_count' => $preset->tyreCount(),
            'axle_count' => $preset->axleCount(),
            'layout_json' => $layout,
            'asset_type' => $preset->suggestedAssetType()->value,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['layout_json'])) {
            $preset = PredefinedTyreLayout::PowerUnit10;
            $data['tyre_count'] = $preset->tyreCount();
            $data['axle_count'] = $preset->axleCount();
            $data['layout_json'] = app(\App\Services\VehicleTyreLayoutBuilder::class)
                ->buildLayout($preset->tyreCount(), $preset->axleCount(), $preset->positionPrefix());
        }

        return $data;
    }
}
