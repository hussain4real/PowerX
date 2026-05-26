<?php

namespace App\Filament\Resources\LessonProgress\Schemas;

use App\Models\LessonProgress;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LessonProgressInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('enrollment.id')
                    ->label('Enrollment'),
                TextEntry::make('lesson.title')
                    ->label('Lesson'),
                TextEntry::make('lesson_content_revision')
                    ->numeric(),
                TextEntry::make('progress_percentage')
                    ->numeric(),
                TextEntry::make('last_position_seconds')
                    ->numeric(),
                TextEntry::make('started_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('completed_at')
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
                    ->visible(fn (LessonProgress $record): bool => $record->trashed()),
            ]);
    }
}
