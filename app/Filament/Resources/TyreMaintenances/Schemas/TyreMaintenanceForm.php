<?php

namespace App\Filament\Resources\TyreMaintenances\Schemas;

use App\Enums\AssignmentAssetType;
use App\Enums\MaintenanceProblemType;
use App\Models\Tyre;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TyreMaintenanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('maintenance_no')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Auto-generated'),
                Select::make('tyre_id')
                    ->label('Tyre')
                    ->relationship('tyre', 'tyre_code')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        if (! $state) {
                            return;
                        }
                        $tyre = Tyre::query()->find($state);
                        if ($tyre) {
                            $set('asset_type', $tyre->current_location_type?->value === 'trailer'
                                ? AssignmentAssetType::Trailer->value
                                : ($tyre->current_location_type?->value === 'power_vehicle'
                                    ? AssignmentAssetType::PowerVehicle->value
                                    : null));
                            $set('asset_id', $tyre->current_location_id);
                            $set('position_code', $tyre->current_position_code);
                        }
                    }),
                Select::make('problem_type')
                    ->options(MaintenanceProblemType::class)
                    ->required(),
                DatePicker::make('maintenance_date')
                    ->required()
                    ->default(now()),
                DatePicker::make('next_inspection_date'),
                TextInput::make('technician'),
                TextInput::make('cost')->numeric()->prefix('ETB'),
                Select::make('asset_type')
                    ->options(AssignmentAssetType::class),
                TextInput::make('asset_id')->numeric()->label('Asset ID'),
                TextInput::make('position_code'),
                Textarea::make('action_taken')->columnSpanFull(),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
