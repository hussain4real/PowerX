<?php

namespace App\Filament\Resources\TrainingBatches\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TrainingBatchForm
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
                Select::make('instructor_id')
                    ->relationship('instructor', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('delivery_mode')
                    ->required()
                    ->default('classroom'),
                TextInput::make('venue'),
                TextInput::make('capacity')
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('scheduled'),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
