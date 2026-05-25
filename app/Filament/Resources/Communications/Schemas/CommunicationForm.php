<?php

namespace App\Filament\Resources\Communications\Schemas;

use App\Models\Communication;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CommunicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name'),
                Select::make('lead_id')
                    ->relationship('lead', 'name'),
                Select::make('student_profile_id')
                    ->relationship('studentProfile', 'id'),
                Select::make('company_id')
                    ->relationship('company', 'name'),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('channel')
                    ->options([
                        Communication::CHANNEL_EMAIL => 'Email',
                        Communication::CHANNEL_WHATSAPP => 'WhatsApp',
                        Communication::CHANNEL_SMS => 'SMS',
                        Communication::CHANNEL_PHONE => 'Phone',
                    ])
                    ->required()
                    ->default(Communication::CHANNEL_EMAIL),
                Select::make('template_key')
                    ->options(fn (): array => collect(config('powerx_notifications.templates'))
                        ->keys()
                        ->mapWithKeys(fn (string $key): array => [$key => str($key)->replace('_', ' ')->title()->toString()])
                        ->all())
                    ->searchable(),
                TextInput::make('subject'),
                Textarea::make('message')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        Communication::STATUS_DRAFT => 'Draft',
                        Communication::STATUS_SCHEDULED => 'Scheduled',
                        Communication::STATUS_SENT => 'Sent',
                        Communication::STATUS_FAILED => 'Failed',
                    ])
                    ->required()
                    ->default(Communication::STATUS_DRAFT),
                DateTimePicker::make('scheduled_at'),
                DateTimePicker::make('sent_at'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
