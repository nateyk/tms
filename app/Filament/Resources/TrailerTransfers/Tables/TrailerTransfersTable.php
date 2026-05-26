<?php

namespace App\Filament\Resources\TrailerTransfers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TrailerTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transfer_no')
                    ->searchable(),
                TextColumn::make('trailer_vehicle_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fromPowerVehicle.id')
                    ->searchable(),
                TextColumn::make('toPowerVehicle.id')
                    ->searchable(),
                TextColumn::make('transfer_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('from_odometer')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('to_odometer')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('prepared_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('checked_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('approved_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
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
