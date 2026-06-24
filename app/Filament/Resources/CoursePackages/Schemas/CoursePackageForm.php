<?php

namespace App\Filament\Resources\CoursePackages\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CoursePackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Section::make('Package details')
                    ->columns(2)
                    ->schema([
                        PowerXForm::relationshipSelect('course_id', 'course', 'title')
                            ->required(),
                        Select::make('package_type')
                            ->options([
                                'standard' => 'Standard',
                                'premium' => 'Premium',
                                'corporate' => 'Corporate',
                            ])
                            ->required()
                            ->default('standard')
                            ->native(false),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columnSpanFull(),
                Section::make('Pricing and access')
                    ->columns(2)
                    ->schema([
                        PowerXForm::currencySelect(),
                        PowerXForm::moneyInput('price')
                            ->required()
                            ->default(0),
                        PowerXForm::moneyInput('discount_price'),
                        PowerXForm::integerInput('validity_days'),
                        PowerXForm::integerInput('max_exam_attempts'),
                    ])
                    ->columnSpanFull(),
                Section::make('Package flags')
                    ->columns(3)
                    ->schema([
                        Toggle::make('includes_certificate')
                            ->required(),
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
                        Toggle::make('allows_free_preview')
                            ->required(),
                        Toggle::make('is_active')
                            ->required(),
                    ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
