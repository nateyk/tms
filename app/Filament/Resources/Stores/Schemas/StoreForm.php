<?php

namespace App\Filament\Resources\Stores\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->unique(ignoreRecord: true),
            TextInput::make('name')->required(),
            Textarea::make('address')->columnSpanFull(),
            TextInput::make('phone'),
            Toggle::make('is_default')->label('Default store'),
            TextInput::make('status')->default('active'),
            Textarea::make('notes')->columnSpanFull(),
        ]);
    }
}
