<?php

namespace App\Filament\Resources\Tyres\Schemas;

use App\Models\Tyre;
use App\Services\TyreQrCodeService;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TyreInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tyre_code'),
                TextEntry::make('serial_number'),
                TextEntry::make('brand.name')
                    ->label('Brand')
                    ->placeholder('-'),
                TextEntry::make('size.id')
                    ->label('Size')
                    ->placeholder('-'),
                TextEntry::make('pattern')
                    ->placeholder('-'),
                TextEntry::make('supplier')
                    ->placeholder('-'),
                TextEntry::make('purchase_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('purchase_price')
                    ->money(),
                TextEntry::make('invoice_number')
                    ->placeholder('-'),
                TextEntry::make('initial_tread_depth')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('current_tread_depth')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('source')
                    ->badge(),
                TextEntry::make('current_location_type')
                    ->badge(),
                TextEntry::make('current_location_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('current_position_code')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                ImageEntry::make('qr_code_path')
                    ->label('QR Code')
                    ->disk('public')
                    ->visibility('public')
                    ->imageSize(220)
                    ->visible(fn (Tyre $record): bool => filled($record->qr_code_path)),
                TextEntry::make('qr_scan_url')
                    ->label('Scan profile URL')
                    ->state(fn (Tyre $record): string => route('tyres.scan', $record->tyre_code))
                    ->url(fn (Tyre $record): string => route('tyres.scan', $record->tyre_code))
                    ->openUrlInNewTab()
                    ->copyable(),
                TextEntry::make('qr_public_url')
                    ->label('QR image URL')
                    ->state(fn (Tyre $record): ?string => app(TyreQrCodeService::class)->publicUrl($record))
                    ->placeholder('-')
                    ->visible(fn (Tyre $record): bool => filled($record->qr_code_path)),
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
                    ->visible(fn (Tyre $record): bool => $record->trashed()),
            ]);
    }
}
