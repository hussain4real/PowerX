<?php

namespace App\Filament\Resources\TrainingSessions\Schemas;

use App\Models\TrainingSession;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TrainingSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('trainingBatch.name')
                    ->label('Training batch'),
                TextEntry::make('title'),
                TextEntry::make('session_type'),
                TextEntry::make('venue')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('starts_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('ends_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('metadata')
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
                    ->visible(fn (TrainingSession $record): bool => $record->trashed()),
            ]);
    }
}
