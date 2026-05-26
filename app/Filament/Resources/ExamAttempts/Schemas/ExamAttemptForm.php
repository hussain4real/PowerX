<?php

namespace App\Filament\Resources\ExamAttempts\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExamAttemptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Callout::make('Attempt review')
                    ->description('Exam attempts are normally created and scored by the exam workflow. Edit only for support corrections.')
                    ->warning()
                    ->columnSpanFull(),
                Section::make('Attempt summary')
                    ->columns(2)
                    ->schema([
                        PowerXForm::relationshipSelect('exam_id', 'exam', 'title')
                            ->required(),
                        PowerXForm::enrollmentSelect(),
                        PowerXForm::studentProfileSelect()
                            ->required(),
                        PowerXForm::integerInput('attempt_number')
                            ->required()
                            ->minValue(1)
                            ->default(1)
                            ->disabledOn('edit'),
                        Select::make('result')
                            ->options([
                                'pending' => 'Pending',
                                'passed' => 'Passed',
                                'failed' => 'Failed',
                                'abandoned' => 'Abandoned',
                            ])
                            ->required()
                            ->default('pending')
                            ->native(false),
                        PowerXForm::percentageInput('score'),
                        PowerXForm::integerInput('duration_seconds'),
                        DateTimePicker::make('started_at'),
                        DateTimePicker::make('submitted_at')
                            ->afterOrEqual('started_at'),
                    ])
                    ->columnSpanFull(),
                Section::make('Submitted answers')
                    ->description('Stored answer payload used by scoring and analytics.')
                    ->schema([
                        Repeater::make('answers')
                            ->schema([
                                PowerXForm::integerInput('question_id')
                                    ->required()
                                    ->minValue(1),
                                TagsInput::make('answer')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
