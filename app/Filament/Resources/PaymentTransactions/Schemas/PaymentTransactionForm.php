<?php

namespace App\Filament\Resources\PaymentTransactions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name'),
                Select::make('enrollment_id')
                    ->relationship('enrollment', 'id'),
                Select::make('invoice_id')
                    ->relationship('invoice', 'id'),
                Select::make('company_id')
                    ->relationship('company', 'name'),
                Select::make('student_profile_id')
                    ->relationship('studentProfile', 'id'),
                Select::make('approved_by_id')
                    ->relationship('approvedBy', 'name')
                    ->disabled()
                    ->helperText('Use the approve action to record audited approvals.'),
                TextInput::make('method')
                    ->required()
                    ->default('bank_transfer'),
                TextInput::make('provider'),
                TextInput::make('reference'),
                TextInput::make('status')
                    ->required()
                    ->default('pending')
                    ->disabled()
                    ->helperText('Payment status is controlled by finance approval actions.'),
                TextInput::make('currency')
                    ->required()
                    ->default('QAR'),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('paid_at'),
                DateTimePicker::make('approved_at')
                    ->disabled(),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
