<?php

namespace App\Filament\Resources\TrainingSessions\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TrainingSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Session schedule')
                    ->columns(2)
                    ->schema([
                        Select::make('training_batch_id')
                            ->relationship(
                                'trainingBatch',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => PowerXForm::teamScoped($query),
                            )
                            ->searchable()
                            ->required(),
                        Select::make('session_type')
                            ->options([
                                'theory' => 'Theory',
                                'practical' => 'Practical',
                                'assessment' => 'Assessment',
                                'make_up' => 'Make-up',
                            ])
                            ->required()
                            ->default('theory')
                            ->native(false),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('venue')
                            ->maxLength(255),
                        Select::make('status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'rescheduled' => 'Rescheduled',
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
