<?php

namespace App\Filament\Resources\TyreMovements\Schemas;

use App\Enums\MovementType;
use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\Vehicle;
use App\Services\TyreMapWorkflowService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TyreMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Movement')
                    ->columns(2)
                    ->schema([
                        TextInput::make('movement_no')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated on save'),
                        Select::make('movement_type')
                            ->label('Movement type')
                            ->options(MovementType::class)
                            ->required()
                            ->live(),
                        DatePicker::make('movement_date')
                            ->label('Movement date')
                            ->required()
                            ->default(now()),
                        Placeholder::make('workflow_hint')
                            ->label('Workflow')
                            ->content('Save as draft → Submit → Check → Approve → Complete (inventory updates on complete)')
                            ->columnSpanFull(),
                    ]),

                Section::make('Tyre')
                    ->description('For store-to-vehicle installs, choose an available tyre from the main store.')
                    ->schema([
                        Select::make('tyre_id')
                            ->label('Tyre')
                            ->relationship(
                                name: 'tyre',
                                titleAttribute: 'tyre_code',
                                modifyQueryUsing: fn ($query) => $query
                                    ->whereIn('status', [
                                        TyreStatus::Available,
                                        TyreStatus::Active,
                                        TyreStatus::Maintenance,
                                    ])
                                    ->orderBy('tyre_code'),
                            )
                            ->getOptionLabelFromRecordUsing(function (Tyre $record): string {
                                $status = $record->status instanceof TyreStatus
                                    ? $record->status
                                    : TyreStatus::tryFrom((string) $record->status);

                                return "{$record->tyre_code} - ".($status?->label() ?? 'Unknown');
                            })
                            ->searchable(['tyre_code', 'serial_number'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if (! $state) {
                                    return;
                                }
                                $tyre = Tyre::query()->find($state);
                                if ($tyre) {
                                    $set('from_location_type', $tyre->current_location_type?->value);
                                    $set('from_location_id', $tyre->current_location_id);
                                    $set('from_position_code', $tyre->current_position_code);
                                }
                            }),
                    ]),

                Section::make('From (current location)')
                    ->columns(2)
                    ->schema([
                        Select::make('from_location_type')
                            ->label('From type')
                            ->options(TyreLocationType::class)
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('from_location_id')
                            ->label('From location ID')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('from_position_code')
                            ->label('From position')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('from_odometer')
                            ->label('From odometer (km)')
                            ->numeric(),
                    ]),

                Section::make('To (destination)')
                    ->description('Select vehicle/trailer and a position from its predefined layout.')
                    ->columns(2)
                    ->schema([
                        Select::make('to_location_type')
                            ->label('To type')
                            ->options(TyreLocationType::class)
                            ->required()
                            ->live(),
                        Select::make('to_location_id')
                            ->label('To vehicle / store')
                            ->options(fn (Get $get) => self::locationOptions($get('to_location_type')))
                            ->searchable()
                            ->required()
                            ->live(),
                        Select::make('to_position_code')
                            ->label('To position')
                            ->options(fn (Get $get) => self::positionOptions(
                                $get('to_location_type'),
                                $get('to_location_id'),
                            ))
                            ->searchable()
                            ->required(fn (Get $get) => in_array($get('to_location_type'), [
                                TyreLocationType::PowerVehicle->value,
                                TyreLocationType::Trailer->value,
                            ], true))
                            ->helperText('Positions come from the vehicle type layout preset.'),
                        TextInput::make('to_odometer')
                            ->label('To odometer (km)')
                            ->numeric(),
                    ]),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * @return array<int|string, string>
     */
    protected static function locationOptions(?string $locationType): array
    {
        return match ($locationType) {
            TyreLocationType::Store->value, 'store' => Store::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
            TyreLocationType::PowerVehicle->value, 'power_vehicle', 'rigid_truck' => Vehicle::query()
                ->whereIn('asset_type', ['power_vehicle', 'rigid_truck'])
                ->orderBy('vehicle_code')
                ->pluck('vehicle_code', 'id')
                ->all(),
            TyreLocationType::Trailer->value, 'trailer' => Vehicle::query()
                ->where('asset_type', 'trailer')
                ->orderBy('vehicle_code')
                ->pluck('vehicle_code', 'id')
                ->all(),
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    protected static function positionOptions(?string $locationType, mixed $locationId): array
    {
        if (! in_array($locationType, [
            TyreLocationType::PowerVehicle->value,
            TyreLocationType::Trailer->value,
            'power_vehicle',
            'trailer',
        ], true) || ! $locationId) {
            return [];
        }

        return app(TyreMapWorkflowService::class)->positionOptionsForVehicle((int) $locationId);
    }

}
