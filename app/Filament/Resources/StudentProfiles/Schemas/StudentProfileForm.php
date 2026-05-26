<?php

namespace App\Filament\Resources\StudentProfiles\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class StudentProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Tabs::make('Student profile')
                    ->tabs([
                        Tab::make('Identity')
                            ->schema([
                                Section::make('Learner details')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('full_name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('email')
                                            ->label('Email address')
                                            ->email()
                                            ->maxLength(255),
                                        TextInput::make('mobile')
                                            ->tel()
                                            ->maxLength(50),
                                        TextInput::make('profession')
                                            ->maxLength(255),
                                    ]),
                            ]),
                        Tab::make('Organization')
                            ->schema([
                                Section::make('Account and company link')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('user_id')
                                            ->relationship('user', 'name')
                                            ->searchable(),
                                        PowerXForm::relationshipSelect('company_id', 'company', 'name'),
                                        TextInput::make('qatar_location')
                                            ->maxLength(255),
                                        TextInput::make('preferred_schedule')
                                            ->maxLength(255),
                                    ]),
                            ]),
                        Tab::make('Documents')
                            ->schema([
                                Section::make('Uploaded documents')
                                    ->description('Store learner documents privately for admissions and support review.')
                                    ->schema([
                                        PowerXForm::mediaUpload('student_documents', 'student-documents', [
                                            'application/pdf',
                                            'image/jpeg',
                                            'image/png',
                                            'image/webp',
                                        ], maxSize: 10240, multiple: true, maxFiles: 10)
                                            ->label('Student documents')
                                            ->helperText('ID, professional, or eligibility documents. Do not expose on public or corporate views.'),
                                    ]),
                                Section::make('Document review')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('document_status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'verified' => 'Verified',
                                                'rejected' => 'Rejected',
                                                'expired' => 'Expired',
                                            ])
                                            ->required()
                                            ->default('pending')
                                            ->native(false),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
