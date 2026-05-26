<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Lesson setup')
                    ->tabs([
                        Tab::make('Details')
                            ->schema([
                                Section::make('Lesson identity')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('course_module_id')
                                            ->relationship('courseModule', 'title')
                                            ->searchable()
                                            ->required(),
                                        Select::make('lesson_type')
                                            ->options([
                                                'video' => 'Video',
                                                'document' => 'Document',
                                                'quiz' => 'Quiz',
                                                'practical' => 'Practical',
                                            ])
                                            ->required()
                                            ->default('video')
                                            ->native(false),
                                        TextInput::make('title')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255),
                                        PowerXForm::integerInput('sort_order')
                                            ->required()
                                            ->default(0),
                                        PowerXForm::integerInput('duration_minutes'),
                                    ]),
                            ]),
                        Tab::make('Content')
                            ->schema([
                                Section::make('Lesson body')
                                    ->schema([
                                        RichEditor::make('content')
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Lesson media')
                                    ->description('Keep training files private until enrollment access rules allow viewing or download.')
                                    ->columns(2)
                                    ->schema([
                                        PowerXForm::mediaUpload('lesson_video', 'video', [
                                            'video/mp4',
                                            'video/webm',
                                        ], maxSize: 512000)
                                            ->label('Lesson video')
                                            ->helperText('MP4 or WebM source file. Secure streaming provider selection remains a sign-off decision.'),
                                        PowerXForm::mediaUpload('learning_materials', 'learning-materials', [
                                            'application/pdf',
                                            'application/zip',
                                            'image/jpeg',
                                            'image/png',
                                            'image/webp',
                                        ], maxSize: 51200, multiple: true, maxFiles: 20)
                                            ->label('Learning materials')
                                            ->helperText('PDFs, ZIP packs, or supporting images for enrolled learners.'),
                                    ]),
                            ]),
                        Tab::make('Publishing')
                            ->schema([
                                Section::make('Access controls')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('is_preview')
                                            ->required()
                                            ->helperText('Preview lessons may be shown before payment access is approved.'),
                                        Toggle::make('is_active')
                                            ->required(),
                                    ]),
                                Callout::make('Content lifecycle planning')
                                    ->description('Revision and retirement fields are internal planning markers only. They preserve learner progress and do not choose a storage or streaming provider.')
                                    ->info(),
                                Section::make('Content revision & retirement')
                                    ->columns(2)
                                    ->schema([
                                        PowerXForm::integerInput('content_revision')
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->helperText('Existing progress keeps the revision captured when the lesson was first started.'),
                                        DateTimePicker::make('content_retired_at')
                                            ->helperText('Optional internal retirement marker. It does not hide the lesson by itself.'),
                                        Select::make('replacement_lesson_id')
                                            ->relationship('replacementLesson', 'title')
                                            ->searchable()
                                            ->native(false)
                                            ->helperText('Optional successor lesson for staff reference.'),
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
