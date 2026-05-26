<?php

namespace App\Filament\Resources\AttendanceRecords\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Section::make('Attendance')
                    ->columns(2)
                    ->schema([
                        Select::make('training_session_id')
                            ->relationship('trainingSession', 'title')
                            ->searchable()
                            ->required(),
                        PowerXForm::enrollmentSelect()
                            ->required(),
                        ToggleButtons::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'present' => 'Present',
                                'late' => 'Late',
                                'absent' => 'Absent',
                                'excused' => 'Excused',
                            ])
                            ->required()
                            ->default('pending')
                            ->inline(),
                        DateTimePicker::make('attended_at'),
                        Select::make('marked_by_id')
                            ->relationship('markedBy', 'name')
                            ->searchable()
                            ->disabled(),
                    ])
                    ->columnSpanFull(),
                Section::make('Practical assessment')
                    ->columns(2)
                    ->schema([
                        ToggleButtons::make('practical_outcome')
                            ->options([
                                'passed' => 'Passed',
                                'failed' => 'Failed',
                                'needs_review' => 'Needs review',
                            ])
                            ->inline(),
                        PowerXForm::percentageInput('practical_score'),
                        Select::make('assessed_by_id')
                            ->relationship('assessedBy', 'name')
                            ->searchable()
                            ->disabled(),
                        DateTimePicker::make('assessed_at')
                            ->disabled(),
                        Textarea::make('practical_comments')
                            ->autosize()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
