<?php

namespace App\Filament\Resources\LessonProgress\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LessonProgressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('enrollment_id')
                    ->relationship('enrollment', 'id')
                    ->required(),
                Select::make('lesson_id')
                    ->relationship('lesson', 'title')
                    ->required(),
                TextInput::make('progress_percentage')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('last_position_seconds')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
