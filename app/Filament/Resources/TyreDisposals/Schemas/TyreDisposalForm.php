<?php

namespace App\Filament\Resources\TyreDisposals\Schemas;

use App\Enums\DisposalReason;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TyreDisposalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('disposal_no')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Auto-generated'),
                Select::make('tyre_id')
                    ->label('Tyre')
                    ->relationship('tyre', 'tyre_code')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('disposal_reason')
                    ->options(DisposalReason::class)
                    ->required(),
                TextInput::make('final_km_used')
                    ->numeric()
                    ->label('Final KM used'),
                TextInput::make('final_condition'),
                TextInput::make('estimated_scrap_value')
                    ->numeric()
                    ->prefix('ETB'),
                TextInput::make('sold_amount')
                    ->numeric()
                    ->prefix('ETB'),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
