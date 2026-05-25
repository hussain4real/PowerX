<?php

namespace App\Filament\Resources\PaymentTransactions\Schemas;

use App\Models\PaymentTransaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PaymentTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('team.name')
                    ->label('Team')
                    ->placeholder('-'),
                TextEntry::make('enrollment.id')
                    ->label('Enrollment')
                    ->placeholder('-'),
                TextEntry::make('invoice.id')
                    ->label('Invoice')
                    ->placeholder('-'),
                TextEntry::make('company.name')
                    ->label('Company')
                    ->placeholder('-'),
                TextEntry::make('studentProfile.id')
                    ->label('Student profile')
                    ->placeholder('-'),
                TextEntry::make('approvedBy.name')
                    ->label('Approved by')
                    ->placeholder('-'),
                TextEntry::make('method'),
                TextEntry::make('provider')
                    ->placeholder('-'),
                TextEntry::make('reference')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('currency'),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('paid_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('metadata')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (PaymentTransaction $record): bool => $record->trashed()),
            ]);
    }
}
