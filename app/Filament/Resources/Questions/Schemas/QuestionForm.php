<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuestionForm
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
                TextInput::make('topic'),
                TextInput::make('difficulty')
                    ->required()
                    ->default('standard'),
                TextInput::make('type')
                    ->required()
                    ->default('single_choice'),
                Textarea::make('question_text')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('options')
                    ->columnSpanFull(),
                Textarea::make('correct_answer')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('explanation')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
