<?php

namespace App\Filament\Resources\Certificates\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name'),
                Select::make('enrollment_id')
                    ->relationship('enrollment', 'id'),
                Select::make('student_profile_id')
                    ->relationship('studentProfile', 'id')
                    ->required(),
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->required(),
                Select::make('approved_by_id')
                    ->relationship('approvedBy', 'name'),
                TextInput::make('certificate_number')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('result')
                    ->required()
                    ->default('passed'),
                DateTimePicker::make('issued_at'),
                DateTimePicker::make('expires_at'),
                DateTimePicker::make('pdf_generated_at'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
