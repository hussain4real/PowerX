<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Tabs::make('Course setup')
                    ->tabs([
                        Tab::make('Overview')
                            ->schema([
                                Section::make('Course identity')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('title')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true),
                                        TextInput::make('category')
                                            ->maxLength(255),
                                        Select::make('delivery_mode')
                                            ->options([
                                                'online' => 'Online',
                                                'classroom' => 'Classroom',
                                                'blended' => 'Blended',
                                            ])
                                            ->required()
                                            ->default('blended')
                                            ->native(false),
                                        Textarea::make('summary')
                                            ->autosize()
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->autosize()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Pricing & access')
                            ->schema([
                                Section::make('Pricing')
                                    ->columns(2)
                                    ->schema([
                                        PowerXForm::currencySelect(),
                                        PowerXForm::moneyInput('base_price')
                                            ->required()
                                            ->default(0),
                                        PowerXForm::integerInput('validity_days')
                                            ->helperText('Leave empty for no default access duration.'),
                                    ]),
                                Section::make('Certificate requirements')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('requires_lesson_completion_for_certificate')
                                            ->label('Require lesson completion')
                                            ->required(),
                                        Toggle::make('requires_exam_pass_for_certificate')
                                            ->label('Require exam pass')
                                            ->required(),
                                        Toggle::make('requires_attendance_for_certificate')
                                            ->label('Require attendance')
                                            ->required(),
                                        Toggle::make('requires_practical_pass_for_certificate')
                                            ->label('Require practical pass')
                                            ->required(),
                                    ]),
                            ]),
                        Tab::make('Media')
                            ->schema([
                                Section::make('Catalog assets')
                                    ->description('Upload private source assets. Public exposure should happen through curated catalog views only.')
                                    ->columns(2)
                                    ->schema([
                                        PowerXForm::imageUpload('cover_image', 'cover-image')
                                            ->label('Cover image')
                                            ->helperText('JPEG, PNG, or WebP. Used for catalog presentation after review.'),
                                        PowerXForm::pdfUpload('syllabus', 'syllabus')
                                            ->label('Syllabus PDF')
                                            ->helperText('Private course syllabus or outline PDF.'),
                                    ]),
                            ]),
                        Tab::make('Publishing')
                            ->schema([
                                Callout::make('Public catalog visibility')
                                    ->description('Published courses can appear on public and student-facing surfaces. Confirm pricing, wording, and certificate claims before publishing.')
                                    ->warning(),
                                Section::make('Publishing controls')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('status')
                                            ->options([
                                                'draft' => 'Draft',
                                                'published' => 'Published',
                                                'archived' => 'Archived',
                                            ])
                                            ->required()
                                            ->default('draft')
                                            ->native(false),
                                        DateTimePicker::make('published_at'),
                                        Toggle::make('is_featured')
                                            ->required(),
                                    ]),
                                Callout::make('Content lifecycle planning')
                                    ->description('Revision and retirement fields are internal planning markers only. They do not revoke existing learner access or choose a storage or streaming provider.')
                                    ->info(),
                                Section::make('Content revision & retirement')
                                    ->columns(2)
                                    ->schema([
                                        PowerXForm::integerInput('content_revision')
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->helperText('Increment when course content is materially revised.'),
                                        DateTimePicker::make('content_retired_at')
                                            ->helperText('Optional internal retirement marker. Keep status and access decisions separate.'),
                                        Select::make('replacement_course_id')
                                            ->relationship('replacementCourse', 'title')
                                            ->searchable()
                                            ->native(false)
                                            ->helperText('Optional successor course for staff reference.'),
                                        Textarea::make('content_retirement_note')
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
