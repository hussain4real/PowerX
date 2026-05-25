<?php

namespace App\Filament\Resources\CoursePackages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CoursePackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name'),
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('package_type')
                    ->required()
                    ->default('standard'),
                TextInput::make('currency')
                    ->required()
                    ->default('QAR'),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
                TextInput::make('discount_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('validity_days')
                    ->numeric(),
                TextInput::make('max_exam_attempts')
                    ->numeric(),
                Toggle::make('includes_certificate')
                    ->required(),
                Toggle::make('allows_free_preview')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
