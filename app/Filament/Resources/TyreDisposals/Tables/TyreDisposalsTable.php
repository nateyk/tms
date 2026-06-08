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
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('disposal_no')
                    ->label('No')
                    ->searchable(),
                TextColumn::make('tyre.id')
                    ->label('Tyre')
                    ->searchable(),
                TextColumn::make('last_location_type')
                    ->label('Location')
                    ->badge()
                    ->searchable(),
                TextColumn::make('last_location_id')
                    ->label('Loc ID')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_position_code')
                    ->label('Pos')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('final_km_used')
                    ->label('KM used')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('final_condition')
                    ->label('Condition')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('disposal_reason')
                    ->label('Reason')
                    ->badge()
                    ->searchable(),
                TextColumn::make('estimated_scrap_value')
                    ->label('Scrap')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sold_amount')
                    ->label('Sold')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('prepared_by')
                    ->label('Prepared')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('checked_by')
                    ->label('Checked')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('approved_by')
                    ->label('Approved')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
