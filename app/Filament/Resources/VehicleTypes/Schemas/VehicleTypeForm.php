<?php

namespace App\Filament\Resources\VehicleTypes\Schemas;

use App\Enums\AssetType;
use App\Enums\PredefinedTyreLayout;
use App\Services\VehicleTyreLayoutBuilder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class VehicleTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vehicle type')
                    ->description('Name and asset category for this layout template.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120),
                        Select::make('asset_type')
                            ->options(AssetType::class)
                            ->required()
                            ->live(),
                        TextInput::make('status')
                            ->required()
                            ->default('active'),
                    ]),

                Section::make('Predefined tyre layout')
                    ->description('Pick a standard axle map. Tyre count, physical axle count, spare wheels, and diagram positions are generated automatically.')
                    ->columns(3)
                    ->schema([
                        Select::make('layout_preset')
                            ->label('Layout preset')
                            ->options(PredefinedTyreLayout::options())
                            ->placeholder('Select a predefined layout...')
                            ->required()
                            ->live()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                if (! $state) {
                                    return;
                                }

                                $preset = PredefinedTyreLayout::tryFrom($state);
                                if (! $preset) {
                                    return;
                                }

                                $layout = app(VehicleTyreLayoutBuilder::class)->buildLayout(
                                    $preset->tyreCount(),
                                    $preset->axleCount(),
                                    $preset->positionPrefix(),
                                );

                                $set('tyre_count', $preset->tyreCount());
                                $set('axle_count', $preset->axleCount());
                                $set('layout_json', $layout);

                                if (! $get('asset_type')) {
                                    $set('asset_type', $preset->suggestedAssetType()->value);
                                }
                            })
                            ->helperText(fn (?string $state): string => $state
                                ? (PredefinedTyreLayout::tryFrom($state)?->description() ?? '')
                                : 'Use Power 10 for standard trucks, Heavy Truck 24 for W/X spare wheel maps, and Trailer 12 for semi-trailers.'),

                        TextInput::make('tyre_count')
                            ->label('Tyre count')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(24)
                            ->helperText('Total position count in the map. Heavy Truck 24 includes W and X spare wheel positions.')
                            ->live(),

                        TextInput::make('axle_count')
                            ->label('Axle count')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(8)
                            ->helperText('Physical axle count, not including spare wheels.')
                            ->live(),

                        Placeholder::make('spare_count')
                            ->label('Spare tyres')
                            ->content(function (Get $get): string {
                                $positions = is_array($get('layout_json'))
                                    ? ($get('layout_json')['positions'] ?? [])
                                    : [];

                                $spares = collect($positions)
                                    ->filter(fn (array $position): bool => in_array($position['display_code'] ?? null, ['W', 'X'], true))
                                    ->count();

                                return $spares > 0
                                    ? "{$spares} spare wheel position(s): W and X"
                                    : 'No spare wheel positions in this layout.';
                            }),

                        Placeholder::make('layout_preview')
                            ->label('Position codes')
                            ->content(function (Get $get): string {
                                $layout = $get('layout_json');
                                $positions = is_array($layout) ? ($layout['positions'] ?? []) : [];
                                if ($positions === []) {
                                    return 'Select a preset to generate positions.';
                                }

                                return collect($positions)
                                    ->map(fn (array $position): string => sprintf(
                                        '%s (%s)',
                                        $position['display_code'] ?? $position['code'],
                                        $position['code'],
                                    ))
                                    ->implode(' | ');
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Advanced')
                    ->collapsed()
                    ->schema([
                        Textarea::make('layout_json')
                            ->label('Layout JSON (advanced)')
                            ->formatStateUsing(fn ($state) => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                : $state)
                            ->dehydrateStateUsing(function (?string $state) {
                                if ($state === null || $state === '') {
                                    return null;
                                }

                                $decoded = json_decode($state, true);

                                return is_array($decoded) ? $decoded : null;
                            })
                            ->rows(12)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
