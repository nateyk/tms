<?php

namespace App\Filament\Resources\TrailerTransfers\Schemas;

use App\Enums\AssetType;
use App\Enums\CombinationStatus;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TrailerTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('transfer_no')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Auto-generated'),
                DatePicker::make('transfer_date')
                    ->required()
                    ->default(now()),
                Select::make('trailer_vehicle_id')
                    ->label('Trailer')
                    ->relationship(
                        'trailer',
                        'vehicle_code',
                        fn (Builder $query) => $query->where('asset_type', AssetType::Trailer)
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $trailerId) {
                        if (! $trailerId) {
                            return;
                        }
                        $combo = VehicleCombination::query()
                            ->where('trailer_vehicle_id', $trailerId)
                            ->where('status', CombinationStatus::Active)
                            ->first();
                        $set('from_power_vehicle_id', $combo?->power_vehicle_id);
                    }),
                Select::make('from_power_vehicle_id')
                    ->label('From power unit')
                    ->relationship(
                        'fromPowerVehicle',
                        'vehicle_code',
                        fn (Builder $query) => $query->where('asset_type', AssetType::PowerVehicle)
                    )
                    ->searchable()
                    ->preload(),
                Select::make('to_power_vehicle_id')
                    ->label('To power unit')
                    ->relationship(
                        'toPowerVehicle',
                        'vehicle_code',
                        fn (Builder $query) => $query->where('asset_type', AssetType::PowerVehicle)
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->different('from_power_vehicle_id'),
                TextInput::make('from_odometer')->numeric(),
                TextInput::make('to_odometer')->numeric(),
                Select::make('location_id')
                    ->relationship('location', 'name')
                    ->searchable(),
                Textarea::make('reason')->columnSpanFull(),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
