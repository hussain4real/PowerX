<?php

namespace App\Filament\Resources\PaymentTransactions\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PaymentTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Tabs::make('Payment workflow')
                    ->tabs([
                        Tab::make('Links')
                            ->schema([
                                Section::make('Related records')
                                    ->columns(2)
                                    ->schema([
                                        PowerXForm::enrollmentSelect(),
                                        PowerXForm::invoiceSelect(),
                                        PowerXForm::relationshipSelect('company_id', 'company', 'name'),
                                        PowerXForm::studentProfileSelect(),
                                    ]),
                            ]),
                        Tab::make('Payment')
                            ->schema([
                                Section::make('Manual payment details')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('method')
                                            ->options([
                                                'bank_transfer' => 'Bank transfer',
                                                'cash' => 'Cash',
                                                'cheque' => 'Cheque',
                                                'card' => 'Card',
                                                'other' => 'Other',
                                            ])
                                            ->required()
                                            ->default('bank_transfer')
                                            ->native(false),
                                        TextInput::make('provider')
                                            ->maxLength(255),
                                        TextInput::make('reference')
                                            ->maxLength(255),
                                        PowerXForm::currencySelect(),
                                        PowerXForm::moneyInput('amount')
                                            ->required(),
                                        DateTimePicker::make('paid_at'),
                                    ]),
                                Section::make('Payment proof')
                                    ->description('Upload bank transfer, cheque, receipt, or cash proof privately for finance review.')
                                    ->schema([
                                        PowerXForm::mediaUpload('payment_proofs', 'payment-proofs', [
                                            'application/pdf',
                                            'image/jpeg',
                                            'image/png',
                                            'image/webp',
                                        ], maxSize: 10240, multiple: true, maxFiles: 5)
                                            ->label('Payment proof files'),
                                    ]),
                            ]),
                        Tab::make('Approval')
                            ->schema([
                                Callout::make('Audited approval')
                                    ->description('Use the table approve action to approve payments. It updates payment, invoice, enrollment, and audit records together.')
                                    ->warning(),
                                Section::make('Approval state')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'approved' => 'Approved',
                                                'rejected' => 'Rejected',
                                                'refunded' => 'Refunded',
                                            ])
                                            ->required()
                                            ->default('pending')
                                            ->disabled()
                                            ->helperText('Payment status is controlled by finance approval actions.'),
                                        Select::make('approved_by_id')
                                            ->relationship('approvedBy', 'name')
                                            ->searchable()
                                            ->disabled()
                                            ->helperText('Use the approve action to record audited approvals.'),
                                        DateTimePicker::make('approved_at')
                                            ->disabled(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
