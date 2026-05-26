<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Models\Vehicle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VehicleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('vehicle_code'),
                TextEntry::make('plate_number')
                    ->placeholder('-'),
                TextEntry::make('asset_type')
                    ->badge(),
                TextEntry::make('vehicleType.name')
                    ->label('Vehicle type'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('currentLocation.name')
                    ->label('Current location')
                    ->placeholder('-'),
                TextEntry::make('odometer')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Vehicle $record): bool => $record->trashed()),
            ]);
    }
}
