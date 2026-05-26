<?php

namespace App\Filament\Resources\Tyres\Schemas;

use App\Enums\TyreSource;
use App\Models\Store;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TyreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('tyre_code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(32),
                TextInput::make('serial_number')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('brand_id')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('size_id')
                    ->relationship('size', 'size_label')
                    ->searchable()
                    ->preload(),
                TextInput::make('pattern'),
                TextInput::make('supplier'),
                Select::make('source')
                    ->options(TyreSource::class)
                    ->required()
                    ->default(TyreSource::PurchasedNewTyre),
                DatePicker::make('purchase_date')->default(now()),
                TextInput::make('purchase_price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('ETB'),
                TextInput::make('invoice_number'),
                TextInput::make('initial_tread_depth')->numeric()->suffix('mm'),
                TextInput::make('current_tread_depth')->numeric()->suffix('mm'),
                Select::make('current_location_id')
                    ->label('Store')
                    ->options(fn () => Store::query()->pluck('name', 'id'))
                    ->default(fn () => Store::query()->where('is_default', true)->value('id'))
                    ->visibleOn('create'),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
