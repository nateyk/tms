<?php

namespace App\Filament\Resources\VehicleTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VehicleTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('asset_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('axle_count')
                    ->label('Axles')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tyre_count')
                    ->label('Tyres')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('spare_count')
                    ->label('Spares')
                    ->state(fn ($record): int => collect($record->layout_json['positions'] ?? [])
                        ->filter(fn (array $position): bool => in_array($position['display_code'] ?? null, ['W', 'X'], true))
                        ->count())
                    ->badge(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
