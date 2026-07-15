<?php

namespace Database\Seeders;

use App\Enums\PredefinedTyreLayout;
use App\Models\Store;
use App\Models\TyreBrand;
use App\Models\TyreSize;
use App\Models\VehicleType;
use App\Services\VehicleTyreLayoutBuilder;
use Illuminate\Database\Seeder;

class FleetOperationalDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        Store::query()->updateOrCreate(
            ['code' => 'MAIN-STORE'],
            [
                'name' => 'Main Tyre Store',
                'address' => 'Main fleet store',
                'is_default' => true,
                'status' => 'active',
            ]
        );

        foreach ($this->brands() as [$name, $code]) {
            TyreBrand::query()->updateOrCreate(
                ['name' => $name],
                ['code' => $code, 'status' => 'active']
            );
        }

        foreach ($this->sizes() as [$label, $code]) {
            TyreSize::query()->updateOrCreate(
                ['size_label' => $label],
                ['code' => $code, 'status' => 'active']
            );
        }

        $builder = app(VehicleTyreLayoutBuilder::class);

        foreach ($this->vehicleTypes() as [$name, $preset]) {
            VehicleType::query()->updateOrCreate(
                ['name' => $name],
                [
                    'asset_type' => $preset->suggestedAssetType()->value,
                    'axle_count' => $preset->axleCount(),
                    'tyre_count' => $preset->tyreCount(),
                    'layout_json' => $builder->buildLayout(
                        $preset->tyreCount(),
                        $preset->axleCount(),
                        $preset->positionPrefix(),
                    ),
                    'status' => 'active',
                ]
            );
        }
    }

    private function brands(): array
    {
        return [
            ['Triangle', 'TRI'],
            ['Black Hawk', 'BLK'],
            ['Michelin', 'MIC'],
            ['Bridgestone', 'BRI'],
            ['DUPRO', 'DUP'],
        ];
    }

    private function sizes(): array
    {
        return [
            ['315/80R22.5', '315-80-225'],
            ['12R22.5', '12R-225'],
            ['385/65R22.5', '385-65-225'],
        ];
    }

    private function vehicleTypes(): array
    {
        return [
            ['Heavy truck - 24 tyres (6 axles + W/X spares)', PredefinedTyreLayout::HeavyTruck24],
            ['Power unit - 10 tyres', PredefinedTyreLayout::PowerUnit10],
            ['Trailer - 12 tyres', PredefinedTyreLayout::Trailer12],
            ['Rigid truck - 6 tyres', PredefinedTyreLayout::RigidTruck6],
        ];
    }
}
