<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('log_name')->toggleable(),
                TextColumn::make('event')->badge()->toggleable(),
                TextColumn::make('description')->limit(50)->searchable(),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? class_basename($state).' #'.$record->subject_id
                        : '—'),
                TextColumn::make('causer.name')->label('User')->default('System'),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
                SelectFilter::make('log_name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
