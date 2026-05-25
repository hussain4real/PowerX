<?php

namespace App\Filament\Resources\TrainingBatches\Schemas;

use App\Models\TrainingBatch;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TrainingBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('team.name')
                    ->label('Team')
                    ->placeholder('-'),
                TextEntry::make('course.title')
                    ->label('Course'),
                TextEntry::make('instructor.name')
                    ->label('Instructor')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('delivery_mode'),
                TextEntry::make('venue')
                    ->placeholder('-'),
                TextEntry::make('capacity')
                    ->numeric()
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
                    ->visible(fn (TrainingBatch $record): bool => $record->trashed()),
            ]);
    }
}
