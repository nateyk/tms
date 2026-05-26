<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('description'),
            TextEntry::make('event')->placeholder('—'),
            TextEntry::make('log_name')->placeholder('—'),
            TextEntry::make('subject_type')->label('Subject type'),
            TextEntry::make('subject_id')->label('Subject ID'),
            TextEntry::make('causer.name')->label('User')->placeholder('System'),
            TextEntry::make('properties')
                ->formatStateUsing(fn ($state) => $state
                    ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : '—')
                ->columnSpanFull(),
        ]);
    }
}
