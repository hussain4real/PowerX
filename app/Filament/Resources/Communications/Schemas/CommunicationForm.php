<?php

namespace App\Filament\Resources\Communications\Schemas;

use App\Filament\Support\PowerXForm;
use App\Models\Communication;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CommunicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Tabs::make('Communication workflow')
                    ->tabs([
                        Tab::make('Recipient')
                            ->schema([
                                Callout::make('Recipient selection')
                                    ->description('Choose the primary recipient context. Avoid selecting unrelated lead, company, student, and user records together unless this is a support note.')
                                    ->info(),
                                Section::make('Recipient')
                                    ->columns(2)
                                    ->schema([
                                        PowerXForm::relationshipSelect('lead_id', 'lead', 'name'),
                                        PowerXForm::studentProfileSelect(),
                                        PowerXForm::relationshipSelect('company_id', 'company', 'name'),
                                        Select::make('user_id')
                                            ->relationship('user', 'name')
                                            ->searchable(),
                                    ]),
                            ]),
                        Tab::make('Message')
                            ->schema([
                                Section::make('Message content')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('channel')
                                            ->options(Communication::channelOptions())
                                            ->required()
                                            ->default(Communication::CHANNEL_EMAIL)
                                            ->native(false),
                                        Select::make('template_key')
                                            ->options(fn (): array => collect(config('powerx_notifications.templates'))
                                                ->keys()
                                                ->mapWithKeys(fn (string $key): array => [$key => str($key)->replace('_', ' ')->title()->toString()])
                                                ->all())
                                            ->searchable(),
                                        TextInput::make('subject')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Textarea::make('message')
                                            ->autosize()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Scheduling')
                            ->schema([
                                Section::make('Delivery state')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('status')
                                            ->options(Communication::statusOptions())
                                            ->required()
                                            ->default(Communication::STATUS_DRAFT)
                                            ->native(false),
                                        DateTimePicker::make('scheduled_at'),
                                        DateTimePicker::make('queued_at')
                                            ->disabled()
                                            ->helperText('Set when a scheduler picks up the communication.'),
                                        DateTimePicker::make('sent_at')
                                            ->disabled()
                                            ->helperText('Set by delivery workflows.'),
                                        DateTimePicker::make('delivered_at')
                                            ->disabled()
                                            ->helperText('Set when delivery confirmation is recorded.'),
                                        DateTimePicker::make('failed_at')
                                            ->disabled()
                                            ->helperText('Set when delivery failure is recorded.'),
                                        DateTimePicker::make('retry_at')
                                            ->helperText('Optional retry time for scheduler pickup.'),
                                        TextInput::make('retry_count')
                                            ->integer()
                                            ->minValue(0)
                                            ->disabled()
                                            ->helperText('Incremented by retry workflows.'),
                                        Textarea::make('failure_reason')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        DateTimePicker::make('opted_out_at')
                                            ->helperText('Set when the recipient should no longer receive automated follow-up.'),
                                        TextInput::make('opt_out_reason')
                                            ->maxLength(255),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
