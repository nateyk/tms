<?php

namespace App\Filament\Resources\Tyres;

use App\Filament\Resources\Tyres\Pages\CreateTyre;
use App\Filament\Resources\Tyres\Pages\EditTyre;
use App\Filament\Resources\Tyres\Pages\ListTyres;
use App\Filament\Resources\Tyres\Pages\ViewTyre;
use App\Filament\Resources\Tyres\Schemas\TyreForm;
use App\Filament\Resources\Tyres\Schemas\TyreInfolist;
use App\Filament\Resources\Tyres\Tables\TyresTable;
use App\Models\Tyre;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TyreResource extends Resource
{
    protected static ?string $model = Tyre::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Tyre Operations';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'tyre_code';

    public static function form(Schema $schema): Schema
    {
        return TyreForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TyreInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TyresTable::configure($table);
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
            'index' => ListTyres::route('/'),
            'create' => CreateTyre::route('/create'),
            'view' => ViewTyre::route('/{record}'),
            'edit' => EditTyre::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
