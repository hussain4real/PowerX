<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Filament\Support\PowerXForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PowerXForm::teamId(),
                Wizard::make([
                    Step::make('Customer')
                        ->description('Link the invoice to the correct student, company, or enrollment.')
                        ->columns(2)
                        ->schema([
                            PowerXForm::enrollmentSelect(),
                            PowerXForm::relationshipSelect('company_id', 'company', 'name'),
                            PowerXForm::studentProfileSelect(),
                        ]),
                    Step::make('Invoice details')
                        ->description('Set the invoice type, number, workflow status, and dates.')
                        ->columns(2)
                        ->schema([
                            TextInput::make('number')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            Select::make('type')
                                ->options([
                                    'quotation' => 'Quotation',
                                    'invoice' => 'Invoice',
                                    'receipt' => 'Receipt',
                                ])
                                ->required()
                                ->default('invoice')
                                ->native(false),
                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'issued' => 'Issued',
                                    'partial' => 'Partial',
                                    'paid' => 'Paid',
                                    'overdue' => 'Overdue',
                                    'cancelled' => 'Cancelled',
                                    'refunded' => 'Refunded',
                                ])
                                ->required()
                                ->default('draft')
                                ->native(false),
                            PowerXForm::currencySelect(),
                            DateTimePicker::make('issued_at'),
                            DateTimePicker::make('due_at')
                                ->afterOrEqual('issued_at'),
                            DateTimePicker::make('paid_at')
                                ->afterOrEqual('issued_at'),
                        ]),
                    Step::make('Amounts')
                        ->description('Use QAR amounts. Totals should match generated PDFs and payment reconciliation.')
                        ->columns(2)
                        ->schema([
                            PowerXForm::moneyInput('subtotal')
                                ->required()
                                ->default(0),
                            PowerXForm::moneyInput('discount_total')
                                ->required()
                                ->default(0),
                            PowerXForm::moneyInput('tax_total')
                                ->required()
                                ->default(0),
                            PowerXForm::moneyInput('total')
                                ->required()
                                ->default(0),
                            Callout::make('Finance review')
                                ->description('Payment approval and invoice synchronization should happen through finance actions so audit events stay consistent.')
                                ->warning()
                                ->columnSpanFull(),
                        ]),
                    Step::make('Generated PDF')
                        ->description('Invoice PDFs are generated through finance actions and shown here for reference.')
                        ->schema([
                            PowerXForm::readOnlyPdfUpload('invoice_pdf', 'invoice-pdf')
                                ->label('Invoice PDF')
                                ->helperText('Read-only: regenerate or replace through the controlled finance document workflow.'),
                        ]),
                ])
                    ->columnSpanFull(),
                PowerXForm::metadataSection(),
            ]);
    }
}
