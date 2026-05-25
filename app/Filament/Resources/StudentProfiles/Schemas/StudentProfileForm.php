<?php

namespace App\Filament\Resources\StudentProfiles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudentProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name'),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('company_id')
                    ->relationship('company', 'name'),
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('mobile'),
                TextInput::make('profession'),
                TextInput::make('qatar_location'),
                TextInput::make('preferred_schedule'),
                TextInput::make('document_status')
                    ->required()
                    ->default('pending'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
