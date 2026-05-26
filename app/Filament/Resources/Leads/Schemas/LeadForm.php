<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Tabs::make('Lead workflow')
                    ->tabs([
                        Tab::make('Contact')
                            ->schema([
                                Section::make('Lead contact')
                                    ->description('Capture the person and their preferred PowerX contact route.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('email')
                                            ->label('Email address')
                                            ->email()
                                            ->maxLength(255),
                                        TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(50),
                                        PowerXForm::relationshipSelect('company_id', 'company', 'name'),
                                    ]),
                            ]),
                        Tab::make('Qualification')
                            ->schema([
                                Section::make('Course interest')
                                    ->description('Classify the source, course interest, and sales owner for follow-up.')
                                    ->columns(2)
                                    ->schema([
                                        PowerXForm::relationshipSelect('course_id', 'course', 'title'),
                                        Select::make('owner_id')
                                            ->relationship('owner', 'name')
                                            ->searchable(),
                                        Select::make('source')
                                            ->options([
                                                'website' => 'Website',
                                                'whatsapp' => 'WhatsApp',
                                                'phone' => 'Phone',
                                                'referral' => 'Referral',
                                                'walk-in' => 'Walk-in',
                                                'campaign' => 'Campaign',
                                                'corporate' => 'Corporate inquiry',
                                            ])
                                            ->searchable()
                                            ->native(false),
                                        TextInput::make('campaign')
                                            ->maxLength(255),
                                        Select::make('status')
                                            ->options([
                                                'new' => 'New',
                                                'contacted' => 'Contacted',
                                                'qualified' => 'Qualified',
                                                'converted' => 'Converted',
                                                'lost' => 'Lost',
                                            ])
                                            ->required()
                                            ->default('new')
                                            ->native(false),
                                        TextInput::make('course_interest')
                                            ->maxLength(255),
                                    ]),
                            ]),
                        Tab::make('Follow-up')
                            ->schema([
                                Callout::make('Lead conversion')
                                    ->description('Use the lead conversion workflow when a lead becomes an enrollment so conversion timestamps and linked records stay auditable.')
                                    ->warning(),
                                Section::make('Follow-up plan')
                                    ->columns(2)
                                    ->schema([
                                        DateTimePicker::make('follow_up_at'),
                                        Select::make('outcome')
                                            ->options([
                                                'interested' => 'Interested',
                                                'registered' => 'Registered',
                                                'not_ready' => 'Not ready',
                                                'no_response' => 'No response',
                                                'disqualified' => 'Disqualified',
                                            ])
                                            ->native(false),
                                        DateTimePicker::make('converted_at')
                                            ->disabled()
                                            ->helperText('Set by conversion workflows.'),
                                        Textarea::make('notes')
                                            ->autosize()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
