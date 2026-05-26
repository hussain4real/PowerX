<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Tabs::make('Question setup')
                    ->tabs([
                        Tab::make('Question')
                            ->columns(2)
                            ->schema([
                                PowerXForm::relationshipSelect('course_id', 'course', 'title')
                                    ->required(),
                                TextInput::make('topic')
                                    ->maxLength(255),
                                Select::make('difficulty')
                                    ->options([
                                        'standard' => 'Standard',
                                        'intermediate' => 'Intermediate',
                                        'advanced' => 'Advanced',
                                    ])
                                    ->required()
                                    ->default('standard')
                                    ->native(false),
                                Select::make('type')
                                    ->options([
                                        'single_choice' => 'Single choice',
                                        'multiple_choice' => 'Multiple choice',
                                        'true_false' => 'True / false',
                                        'short_answer' => 'Short answer',
                                    ])
                                    ->required()
                                    ->default('single_choice')
                                    ->native(false),
                                Textarea::make('question_text')
                                    ->required()
                                    ->autosize()
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Answers')
                            ->schema([
                                Callout::make('Changing answer structure')
                                    ->description('Be careful when changing answer keys on questions that already have exam attempts because analytics compare stored answers to these keys.')
                                    ->warning(),
                                Repeater::make('options')
                                    ->schema([
                                        TextInput::make('key')
                                            ->required()
                                            ->maxLength(10),
                                        TextInput::make('label')
                                            ->required()
                                            ->maxLength(500),
                                    ])
                                    ->columns(2)
                                    ->minItems(2)
                                    ->columnSpanFull(),
                                TagsInput::make('correct_answer')
                                    ->required()
                                    ->helperText('Use option keys such as A, B, C, or accepted text answers.')
                                    ->columnSpanFull(),
                                Textarea::make('explanation')
                                    ->autosize()
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Publishing')
                            ->schema([
                                Toggle::make('is_active')
                                    ->required(),
                            ]),
                    ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
