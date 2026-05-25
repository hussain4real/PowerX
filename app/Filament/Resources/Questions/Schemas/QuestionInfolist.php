<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Question;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class QuestionInfolist
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
                TextEntry::make('topic')
                    ->placeholder('-'),
                TextEntry::make('difficulty'),
                TextEntry::make('type'),
                TextEntry::make('question_text')
                    ->columnSpanFull(),
                TextEntry::make('options')
                    ->formatStateUsing(fn (mixed $state): string => self::formatJson($state))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('correct_answer')
                    ->formatStateUsing(fn (mixed $state): string => self::formatJson($state))
                    ->columnSpanFull(),
                TextEntry::make('explanation')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_active')
                    ->boolean(),
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
                    ->visible(fn (Question $record): bool => $record->trashed()),
            ]);
    }

    private static function formatJson(mixed $state): string
    {
        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
