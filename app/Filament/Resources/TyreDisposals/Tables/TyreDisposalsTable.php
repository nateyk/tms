<?php

namespace App\Filament\Resources\TyreDisposals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TyreDisposalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('disposal_no')
                    ->searchable(),
                TextColumn::make('tyre.id')
                    ->searchable(),
                TextColumn::make('last_location_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('last_location_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_position_code')
                    ->searchable(),
                TextColumn::make('final_km_used')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('final_condition')
                    ->searchable(),
                TextColumn::make('disposal_reason')
                    ->badge()
                    ->searchable(),
                TextColumn::make('estimated_scrap_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sold_amount')
                    ->numeric()
                    ->sortable(),
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
