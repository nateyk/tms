<?php

namespace App\Filament\Resources\TyreMaintenances\Tables;

use App\Enums\MaintenanceStatus;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TyreMaintenancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('maintenance_no')->searchable()->sortable(),
                TextColumn::make('tyre.tyre_code')->label('Tyre')->searchable(),
                TextColumn::make('problem_type')->badge(),
                TextColumn::make('maintenance_date')->date()->sortable(),
                TextColumn::make('cost')->money('ETB')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('technician')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(MaintenanceStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
