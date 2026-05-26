<?php

namespace App\Filament\Resources\TrailerTransfers;

use App\Filament\Resources\TrailerTransfers\Pages\CreateTrailerTransfer;
use App\Filament\Resources\TrailerTransfers\Pages\EditTrailerTransfer;
use App\Filament\Resources\TrailerTransfers\Pages\ListTrailerTransfers;
use App\Filament\Resources\TrailerTransfers\Schemas\TrailerTransferForm;
use App\Filament\Resources\TrailerTransfers\Tables\TrailerTransfersTable;
use App\Models\TrailerTransfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TrailerTransferResource extends Resource
{
    protected static ?string $model = TrailerTransfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Fleet';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return TrailerTransferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrailerTransfersTable::configure($table);
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
            'index' => ListTrailerTransfers::route('/'),
            'create' => CreateTrailerTransfer::route('/create'),
            'edit' => EditTrailerTransfer::route('/{record}/edit'),
        ];
    }
}
