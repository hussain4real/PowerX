<?php

namespace App\Filament\Resources\Exams\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name'),
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('exam_type')
                    ->required()
                    ->default('mock'),
                TextInput::make('duration_minutes')
                    ->required()
                    ->numeric()
                    ->default(60),
                TextInput::make('pass_mark')
                    ->required()
                    ->numeric()
                    ->default(70),
                TextInput::make('max_attempts')
                    ->required()
                    ->numeric()
                    ->default(3),
                TextInput::make('question_count')
                    ->numeric(),
                Toggle::make('randomize_questions')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
