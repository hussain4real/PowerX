<?php

namespace App\Filament\Resources\CourseModules\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Module details')
                    ->columns(2)
                    ->schema([
                        PowerXForm::relationshipSelect('course_id', 'course', 'title')
                            ->required(),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        PowerXForm::integerInput('sort_order')
                            ->required()
                            ->default(0),
                        Toggle::make('is_active')
                            ->required(),
                        Textarea::make('summary')
                            ->autosize()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
