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
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('movement_no')->label('No')->searchable()->sortable(),
                TextColumn::make('tyre.tyre_code')->label('Tyre')->searchable(),
                TextColumn::make('movement_type')->label('Type')->badge(),
                TextColumn::make('movement_date')->label('Date')->date()->sortable(),
                TextColumn::make('from_location_type')->label('From')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('to_location_type')->label('To')->badge()->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('preparedByUser.name')->label('Prepared')->toggleable(isToggledHiddenByDefault: true),
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
