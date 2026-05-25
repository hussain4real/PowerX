<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name'),
                Select::make('enrollment_id')
                    ->relationship('enrollment', 'id'),
                Select::make('company_id')
                    ->relationship('company', 'name'),
                Select::make('student_profile_id')
                    ->relationship('studentProfile', 'id'),
                TextInput::make('number')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->default('invoice'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('currency')
                    ->required()
                    ->default('QAR'),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('tax_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('issued_at'),
                DateTimePicker::make('due_at'),
                DateTimePicker::make('paid_at'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
