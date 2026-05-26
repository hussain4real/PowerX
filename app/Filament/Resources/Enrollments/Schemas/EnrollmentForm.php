<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Wizard::make([
                    Step::make('Learner')
                        ->description('Select the student or corporate account.')
                        ->columns(2)
                        ->schema([
                            PowerXForm::studentProfileSelect()
                                ->required(),
                            PowerXForm::relationshipSelect('company_id', 'company', 'name'),
                        ]),
                    Step::make('Course')
                        ->description('Choose the course and package for this enrollment.')
                        ->columns(2)
                        ->schema([
                            PowerXForm::relationshipSelect('course_id', 'course', 'title')
                                ->live()
                                ->required(),
                            Select::make('course_package_id')
                                ->relationship(
                                    'coursePackage',
                                    'name',
                                    modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                                        ->when($get('course_id'), fn (Builder $query, int $courseId): Builder => $query->where('course_id', $courseId))
                                        ->when(! $get('course_id'), fn (Builder $query): Builder => $query->whereRaw('1 = 0')),
                                )
                                ->searchable(),
                        ]),
                    Step::make('Access')
                        ->description('Set approval, payment, and access window details.')
                        ->columns(2)
                        ->schema([
                            Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'approved' => 'Approved',
                                    'active' => 'Active',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->required()
                                ->default('pending')
                                ->native(false),
                            Select::make('payment_status')
                                ->options([
                                    'pending' => 'Pending',
                                    'partial' => 'Partial',
                                    'paid' => 'Paid',
                                    'refunded' => 'Refunded',
                                ])
                                ->required()
                                ->default('pending')
                                ->native(false),
                            DateTimePicker::make('access_starts_at'),
                            DateTimePicker::make('access_expires_at')
                                ->afterOrEqual('access_starts_at'),
                            Select::make('approved_by_id')
                                ->relationship('approvedBy', 'name')
                                ->searchable()
                                ->disabled()
                                ->helperText('Set by approval workflows where possible.'),
                            DateTimePicker::make('approved_at')
                                ->disabled(),
                            Callout::make('Access implications')
                                ->description('Active and paid enrollments can unlock private lessons, exams, schedules, and certificates.')
                                ->info()
                                ->columnSpanFull(),
                        ]),
                    Step::make('Notes')
                        ->schema([
                            Textarea::make('notes')
                                ->autosize()
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
