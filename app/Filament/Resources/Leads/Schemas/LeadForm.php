<?php

namespace App\Filament\Resources\Leads\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name'),
                Select::make('owner_id')
                    ->relationship('owner', 'name'),
                Select::make('company_id')
                    ->relationship('company', 'name'),
                Select::make('course_id')
                    ->relationship('course', 'title'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('source'),
                TextInput::make('campaign'),
                TextInput::make('status')
                    ->required()
                    ->default('new'),
                TextInput::make('course_interest'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DateTimePicker::make('follow_up_at'),
                TextInput::make('outcome'),
                DateTimePicker::make('converted_at'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
