<?php

namespace App\Filament\Resources\Exams\Schemas;

use App\Models\Exam;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExamInfolist
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
                TextEntry::make('title'),
                TextEntry::make('exam_type'),
                TextEntry::make('duration_minutes')
                    ->numeric(),
                TextEntry::make('pass_mark')
                    ->numeric(),
                TextEntry::make('max_attempts')
                    ->numeric(),
                TextEntry::make('question_count')
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('randomize_questions')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->boolean(),
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
                    ->visible(fn (Exam $record): bool => $record->trashed()),
            ]);
    }
}
