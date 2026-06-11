<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Enums\AssetType;
use App\Enums\VehicleStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Fieldset::make('Asset identity')
                    ->columns(2)
                    ->schema([
                        TextInput::make('vehicle_code')
                            ->label('Vehicle / asset code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('plate_number')
                            ->label('Plate number')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('chassis_number')
                            ->label('Chassis number')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('engine_number')
                            ->label('Engine number')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ]),
                Fieldset::make('Asset setup')
                    ->columns(2)
                    ->schema([
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
                        TextInput::make('manufacture_year')
                            ->numeric()
                            ->minValue(1980)
                            ->maxValue((int) now()->addYear()->format('Y')),
                        TextInput::make('odometer')
                            ->numeric(),
                    ]),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
