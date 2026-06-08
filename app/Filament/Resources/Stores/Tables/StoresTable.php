<?php

namespace App\Filament\Resources\Stores\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoresTable
{
    public static function configure(Table $table): Table
    {
        return $table->striped()->paginated([10, 25, 50])->defaultPaginationPageOption(10)->columns([
            TextColumn::make('code')->searchable()->sortable(),
            TextColumn::make('name')->searchable(),
            IconColumn::make('is_default')->boolean()->label('Default'),
            TextColumn::make('status')->badge(),
        ])->recordActions([
            EditAction::make(),
        ]);
    }
}
