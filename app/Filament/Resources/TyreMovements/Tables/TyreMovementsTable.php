<?php

namespace App\Filament\Resources\TyreMovements\Tables;

use App\Enums\VoucherStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TyreMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('movement_no')->searchable()->sortable(),
                TextColumn::make('tyre.tyre_code')->label('Tyre')->searchable(),
                TextColumn::make('movement_type')->badge(),
                TextColumn::make('movement_date')->date()->sortable(),
                TextColumn::make('from_location_type')->badge()->toggleable(),
                TextColumn::make('to_location_type')->badge()->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('preparedByUser.name')->label('Prepared by')->toggleable(),
                TextColumn::make('completed_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(VoucherStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
