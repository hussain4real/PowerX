<?php

namespace App\Filament\Resources\AttendanceRecords\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttendanceRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name'),
                Select::make('training_session_id')
                    ->relationship('trainingSession', 'title')
                    ->required(),
                Select::make('enrollment_id')
                    ->relationship('enrollment', 'id')
                    ->required(),
                Select::make('marked_by_id')
                    ->relationship('markedBy', 'name'),
                Select::make('assessed_by_id')
                    ->relationship('assessedBy', 'name'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                DateTimePicker::make('attended_at'),
                TextInput::make('practical_outcome'),
                TextInput::make('practical_score')
                    ->numeric(),
                Textarea::make('practical_comments')
                    ->columnSpanFull(),
                DateTimePicker::make('assessed_at'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
