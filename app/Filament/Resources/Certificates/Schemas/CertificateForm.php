<?php

namespace App\Filament\Resources\Certificates\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Tabs::make('Certificate workflow')
                    ->tabs([
                        Tab::make('Candidate')
                            ->schema([
                                Section::make('Candidate and course')
                                    ->columns(2)
                                    ->schema([
                                        PowerXForm::enrollmentSelect(),
                                        PowerXForm::studentProfileSelect()
                                            ->required(),
                                        PowerXForm::relationshipSelect('course_id', 'course', 'title')
                                            ->required(),
                                        Select::make('approved_by_id')
                                            ->relationship('approvedBy', 'name')
                                            ->searchable()
                                            ->disabled()
                                            ->helperText('Set by certificate issuance workflow where possible.'),
                                    ]),
                            ]),
                        Tab::make('Certificate')
                            ->schema([
                                Callout::make('Public verification')
                                    ->description('Issued certificates may be externally verified. Confirm wording, result, and validity dates before issuing.')
                                    ->warning(),
                                Section::make('Certificate details')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('certificate_number')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true),
                                        Select::make('status')
                                            ->options([
                                                'draft' => 'Draft',
                                                'issued' => 'Issued',
                                                'revoked' => 'Revoked',
                                                'expired' => 'Expired',
                                            ])
                                            ->required()
                                            ->default('draft')
                                            ->native(false),
                                        Select::make('result')
                                            ->options([
                                                'passed' => 'Passed',
                                                'failed' => 'Failed',
                                            ])
                                            ->required()
                                            ->default('passed')
                                            ->native(false),
                                        DateTimePicker::make('issued_at'),
                                        DateTimePicker::make('expires_at')
                                            ->afterOrEqual('issued_at'),
                                        DateTimePicker::make('pdf_generated_at')
                                            ->disabled()
                                            ->helperText('Set when certificate PDF generation succeeds.'),
                                    ]),
                                Section::make('Generated certificate PDF')
                                    ->schema([
                                        PowerXForm::readOnlyPdfUpload('certificate_pdf', 'certificate-pdf')
                                            ->label('Certificate PDF')
                                            ->helperText('Read-only: certificate files are attached by the issuance/PDF generation workflow.'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
