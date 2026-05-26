<?php

namespace App\Filament\Resources\TrainingBatches\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrainingBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Section::make('Batch setup')
                    ->columns(2)
                    ->schema([
                        PowerXForm::relationshipSelect('course_id', 'course', 'title')
                            ->required(),
                        Select::make('instructor_id')
                            ->relationship('instructor', 'name')
                            ->searchable(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('delivery_mode')
                            ->options([
                                'classroom' => 'Classroom',
                                'online' => 'Online',
                                'blended' => 'Blended',
                            ])
                            ->required()
                            ->default('classroom')
                            ->native(false),
                        TextInput::make('venue')
                            ->maxLength(255),
                        PowerXForm::integerInput('capacity'),
                        Select::make('status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('scheduled')
                            ->native(false),
                        DateTimePicker::make('starts_at'),
                        DateTimePicker::make('ends_at')
                            ->afterOrEqual('starts_at'),
                    ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
