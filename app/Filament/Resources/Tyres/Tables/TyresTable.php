<?php

namespace App\Filament\Resources\Tyres\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TyresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tyre_code')
                    ->searchable(),
                TextColumn::make('serial_number')
                    ->searchable(),
                TextColumn::make('brand.name')
                    ->searchable(),
                TextColumn::make('size.id')
                    ->searchable(),
                TextColumn::make('pattern')
                    ->searchable(),
                TextColumn::make('supplier')
                    ->searchable(),
                TextColumn::make('purchase_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('invoice_number')
                    ->searchable(),
                TextColumn::make('initial_tread_depth')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('current_tread_depth')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('source')
                    ->badge()
                    ->searchable(),
                TextColumn::make('current_location_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('current_location_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('current_position_code')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('qr_code_path')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
