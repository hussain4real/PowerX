<?php

namespace App\Filament\Resources\LessonProgress\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LessonProgressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Callout::make('Support override')
                    ->description('Lesson progress is normally system-generated. Edit only when supporting a learner or correcting imported progress.')
                    ->warning()
                    ->columnSpanFull(),
                Section::make('Progress record')
                    ->columns(2)
                    ->schema([
                        PowerXForm::enrollmentSelect()
                            ->required(),
                        Select::make('lesson_id')
                            ->relationship('lesson', 'title')
                            ->searchable()
                            ->required(),
                        PowerXForm::integerInput('lesson_content_revision')
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Snapshot captured when the learner first starts this lesson.'),
                        PowerXForm::percentageInput('progress_percentage')
                            ->required()
                            ->default(0),
                        PowerXForm::integerInput('last_position_seconds')
                            ->required()
                            ->default(0),
                        DateTimePicker::make('started_at'),
                        DateTimePicker::make('completed_at')
                            ->afterOrEqual('started_at'),
                    ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
