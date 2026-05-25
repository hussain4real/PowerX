<?php

namespace App\Filament\Resources\ExamAttempts\Schemas;

use App\Models\ExamAttempt;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExamAttemptInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('team.name')
                    ->label('Team')
                    ->placeholder('-'),
                TextEntry::make('exam.title')
                    ->label('Exam'),
                TextEntry::make('enrollment.id')
                    ->label('Enrollment')
                    ->placeholder('-'),
                TextEntry::make('studentProfile.id')
                    ->label('Student profile'),
                TextEntry::make('attempt_number')
                    ->numeric(),
                TextEntry::make('result'),
                TextEntry::make('score')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('duration_seconds')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('answers')
                    ->formatStateUsing(fn (mixed $state): string => self::formatJson($state))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('started_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('submitted_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('metadata')
                    ->formatStateUsing(fn (mixed $state): string => self::formatJson($state))
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
                    ->visible(fn (ExamAttempt $record): bool => $record->trashed()),
            ]);
    }

    private static function formatJson(mixed $state): string
    {
        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
