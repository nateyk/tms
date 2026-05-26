<?php

namespace App\Filament\Resources\TyreMaintenances;

use App\Filament\Resources\TyreMaintenances\Pages\CreateTyreMaintenance;
use App\Filament\Resources\TyreMaintenances\Pages\EditTyreMaintenance;
use App\Filament\Resources\TyreMaintenances\Pages\ListTyreMaintenances;
use App\Filament\Resources\TyreMaintenances\Schemas\TyreMaintenanceForm;
use App\Filament\Resources\TyreMaintenances\Tables\TyreMaintenancesTable;
use App\Models\TyreMaintenance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TyreMaintenanceResource extends Resource
{
    protected static ?string $model = TyreMaintenance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrench;

    protected static string|\UnitEnum|null $navigationGroup = 'Tyre Operations';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return TyreMaintenanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TyreMaintenancesTable::configure($table);
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
            'index' => ListTyreMaintenances::route('/'),
            'create' => CreateTyreMaintenance::route('/create'),
            'edit' => EditTyreMaintenance::route('/{record}/edit'),
        ];
    }
}
