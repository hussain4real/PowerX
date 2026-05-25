<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name'),
                Select::make('student_profile_id')
                    ->relationship('studentProfile', 'id')
                    ->required(),
                Select::make('company_id')
                    ->relationship('company', 'name'),
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->required(),
                Select::make('course_package_id')
                    ->relationship('coursePackage', 'name'),
                Select::make('approved_by_id')
                    ->relationship('approvedBy', 'name'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('payment_status')
                    ->required()
                    ->default('pending'),
                DateTimePicker::make('access_starts_at'),
                DateTimePicker::make('access_expires_at'),
                DateTimePicker::make('approved_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
