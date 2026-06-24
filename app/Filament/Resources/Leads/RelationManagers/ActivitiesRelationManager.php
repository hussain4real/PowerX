<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('channel')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('previous_status')
                    ->placeholder('-')
                    ->badge(),
                TextColumn::make('next_status')
                    ->placeholder('-')
                    ->badge(),
                TextColumn::make('follow_up_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('notes')
                    ->limit(80)
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
