<?php

namespace App\Filament\Resources\TrainingSessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TrainingSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('training_batch_id')
                    ->relationship('trainingBatch', 'name')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('session_type')
                    ->required()
                    ->default('theory'),
                TextInput::make('venue'),
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
