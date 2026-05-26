<?php

namespace App\Filament\Resources\Exams\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Section::make('Exam setup')
                    ->columns(2)
                    ->schema([
                        PowerXForm::relationshipSelect('course_id', 'course', 'title')
                            ->required(),
                        Select::make('exam_type')
                            ->options([
                                'mock' => 'Mock',
                                'practice' => 'Practice',
                                'final' => 'Final',
                            ])
                            ->required()
                            ->default('mock')
                            ->native(false),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        PowerXForm::integerInput('duration_minutes')
                            ->required()
                            ->minValue(1)
                            ->default(60),
                    ])
                    ->columnSpanFull(),
                Section::make('Scoring')
                    ->columns(2)
                    ->schema([
                        PowerXForm::percentageInput('pass_mark')
                            ->required()
                            ->default(70),
                        PowerXForm::integerInput('max_attempts')
                            ->required()
                            ->minValue(1)
                            ->default(3),
                        PowerXForm::integerInput('question_count')
                            ->minValue(1),
                    ])
                    ->columnSpanFull(),
                Section::make('Publishing')
                    ->columns(2)
                    ->schema([
                        Toggle::make('randomize_questions')
                            ->required(),
                        Toggle::make('is_active')
                            ->required(),
                    ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
