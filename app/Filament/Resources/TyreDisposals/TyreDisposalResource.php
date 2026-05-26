<?php

namespace App\Filament\Resources\TyreDisposals;

use App\Filament\Resources\TyreDisposals\Pages\CreateTyreDisposal;
use App\Filament\Resources\TyreDisposals\Pages\EditTyreDisposal;
use App\Filament\Resources\TyreDisposals\Pages\ListTyreDisposals;
use App\Filament\Resources\TyreDisposals\Schemas\TyreDisposalForm;
use App\Filament\Resources\TyreDisposals\Tables\TyreDisposalsTable;
use App\Models\TyreDisposal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TyreDisposalResource extends Resource
{
    protected static ?string $model = TyreDisposal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static string|\UnitEnum|null $navigationGroup = 'Tyre Operations';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return TyreDisposalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TyreDisposalsTable::configure($table);
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
            'index' => ListTyreDisposals::route('/'),
            'create' => CreateTyreDisposal::route('/create'),
            'edit' => EditTyreDisposal::route('/{record}/edit'),
        ];
    }
}
