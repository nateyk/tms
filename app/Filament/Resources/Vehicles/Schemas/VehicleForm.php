<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Enums\AssetType;
use App\Enums\VehicleStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('vehicle_code')
                    ->required(),
                TextInput::make('plate_number'),
                Select::make('asset_type')
                    ->options(AssetType::class)
                    ->required(),
                Select::make('vehicle_type_id')
                    ->relationship('vehicleType', 'name')
                    ->required(),
                Select::make('status')
                    ->options(VehicleStatus::class)
                    ->default('active')
                    ->required(),
                Select::make('current_location_id')
                    ->relationship('currentLocation', 'name'),
                TextInput::make('odometer')
                    ->numeric(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
