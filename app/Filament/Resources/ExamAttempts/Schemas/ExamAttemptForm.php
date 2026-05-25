<?php

namespace App\Filament\Resources\ExamAttempts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExamAttemptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name'),
                Select::make('exam_id')
                    ->relationship('exam', 'title')
                    ->required(),
                Select::make('enrollment_id')
                    ->relationship('enrollment', 'id'),
                Select::make('student_profile_id')
                    ->relationship('studentProfile', 'id')
                    ->required(),
                TextInput::make('attempt_number')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('result')
                    ->required()
                    ->default('pending'),
                TextInput::make('score')
                    ->numeric(),
                TextInput::make('duration_seconds')
                    ->numeric(),
                Textarea::make('answers')
                    ->columnSpanFull(),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('submitted_at'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
