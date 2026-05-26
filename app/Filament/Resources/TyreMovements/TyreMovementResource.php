<?php

namespace App\Filament\Resources\TyreMovements;

use App\Filament\Resources\TyreMovements\Pages\CreateTyreMovement;
use App\Filament\Resources\TyreMovements\Pages\EditTyreMovement;
use App\Filament\Resources\TyreMovements\Pages\ListTyreMovements;
use App\Filament\Resources\TyreMovements\Schemas\TyreMovementForm;
use App\Filament\Resources\TyreMovements\Tables\TyreMovementsTable;
use App\Models\TyreMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TyreMovementResource extends Resource
{
    protected static ?string $model = TyreMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Tyre Operations';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return TyreMovementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TyreMovementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTyreMovements::route('/'),
            'create' => CreateTyreMovement::route('/create'),
            'edit' => EditTyreMovement::route('/{record}/edit'),
        ];
    }
}
