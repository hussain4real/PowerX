<?php

namespace App\Filament\Resources\PaymentTransactions\Tables;

use App\Actions\PowerX\ApproveManualPayment;
use App\Actions\PowerX\ReviewOfflinePayment;
use App\Enums\PowerXPermission;
use App\Models\PaymentTransaction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PaymentTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('team.name')
                    ->searchable(),
                TextColumn::make('enrollment.id')
                    ->searchable(),
                TextColumn::make('invoice.id')
                    ->searchable(),
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('studentProfile.id')
                    ->searchable(),
                TextColumn::make('approvedBy.name')
                    ->searchable(),
                TextColumn::make('method')
                    ->searchable(),
                TextColumn::make('provider')
                    ->searchable(),
                TextColumn::make('reference')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PaymentTransaction::statusOptions()),
                SelectFilter::make('method')
                    ->options(PaymentTransaction::manualMethodOptions()),
                Filter::make('pending_proof')
                    ->label('Pending proof')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', PaymentTransaction::STATUS_PENDING)
                        ->whereDoesntHave('media', fn (Builder $mediaQuery): Builder => $mediaQuery->where('collection_name', 'payment-proofs'))),
                Filter::make('overdue_invoice')
                    ->label('Overdue invoice')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereHas('invoice', fn (Builder $invoiceQuery): Builder => $invoiceQuery
                            ->where('due_at', '<', now())
                            ->where('status', '!=', 'paid'))),
                Filter::make('unmatched')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNull('invoice_id')
                        ->whereNull('enrollment_id')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve manual payment')
                    ->modalDescription('This will approve the payment and synchronize the linked invoice and enrollment.')
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManagePayments->value) ?? false)
                    ->visible(fn (PaymentTransaction $record): bool => in_array($record->status, [
                        PaymentTransaction::STATUS_PENDING,
                        PaymentTransaction::STATUS_INFORMATION_REQUESTED,
                        PaymentTransaction::STATUS_PARTIAL,
                    ], true))
                    ->action(function (PaymentTransaction $record, ApproveManualPayment $approveManualPayment): void {
                        $approver = Auth::user();

                        abort_unless($approver instanceof User, 403);

                        $approveManualPayment->handle($record, $approver);

                        Notification::make()
                            ->title('Payment approved')
                            ->success()
                            ->send();
                    }),
                Action::make('requestInformation')
                    ->label('Request info')
                    ->icon(Heroicon::OutlinedQuestionMarkCircle)
                    ->color('warning')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Information needed')
                            ->required()
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManagePayments->value) ?? false)
                    ->visible(fn (PaymentTransaction $record): bool => self::hasStatus($record, [
                        PaymentTransaction::STATUS_PENDING,
                        PaymentTransaction::STATUS_PARTIAL,
                    ]))
                    ->action(fn (array $data, PaymentTransaction $record, ReviewOfflinePayment $reviewOfflinePayment): null => self::review(
                        $record,
                        $reviewOfflinePayment,
                        'request_information',
                        $data,
                        'Information requested',
                    )),
                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Reason')
                            ->required()
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManagePayments->value) ?? false)
                    ->visible(fn (PaymentTransaction $record): bool => self::hasStatus($record, [
                        PaymentTransaction::STATUS_PENDING,
                        PaymentTransaction::STATUS_INFORMATION_REQUESTED,
                        PaymentTransaction::STATUS_PARTIAL,
                    ]))
                    ->action(fn (array $data, PaymentTransaction $record, ReviewOfflinePayment $reviewOfflinePayment): null => self::review(
                        $record,
                        $reviewOfflinePayment,
                        'reject',
                        $data,
                        'Payment rejected',
                    )),
                Action::make('markDuplicate')
                    ->label('Duplicate')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('gray')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Internal notes')
                            ->required()
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManagePayments->value) ?? false)
                    ->visible(fn (PaymentTransaction $record): bool => self::hasStatus($record, [
                        PaymentTransaction::STATUS_PENDING,
                        PaymentTransaction::STATUS_INFORMATION_REQUESTED,
                        PaymentTransaction::STATUS_PARTIAL,
                    ]))
                    ->action(fn (array $data, PaymentTransaction $record, ReviewOfflinePayment $reviewOfflinePayment): null => self::review(
                        $record,
                        $reviewOfflinePayment,
                        'duplicate',
                        $data,
                        'Payment marked duplicate',
                    )),
                Action::make('markPartial')
                    ->label('Partial')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color('warning')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Internal notes')
                            ->required()
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManagePayments->value) ?? false)
                    ->visible(fn (PaymentTransaction $record): bool => self::hasStatus($record, [
                        PaymentTransaction::STATUS_PENDING,
                        PaymentTransaction::STATUS_INFORMATION_REQUESTED,
                    ]))
                    ->action(fn (array $data, PaymentTransaction $record, ReviewOfflinePayment $reviewOfflinePayment): null => self::review(
                        $record,
                        $reviewOfflinePayment,
                        'partial',
                        $data,
                        'Payment marked partial',
                    )),
                Action::make('void')
                    ->label('Void')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('notes')
                            ->label('Internal notes')
                            ->required()
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManagePayments->value) ?? false)
                    ->visible(fn (PaymentTransaction $record): bool => self::hasStatus($record, [
                        PaymentTransaction::STATUS_APPROVED,
                        PaymentTransaction::STATUS_PARTIAL,
                        PaymentTransaction::STATUS_ADJUSTED,
                    ]))
                    ->action(fn (array $data, PaymentTransaction $record, ReviewOfflinePayment $reviewOfflinePayment): null => self::review(
                        $record,
                        $reviewOfflinePayment,
                        'void',
                        $data,
                        'Payment voided',
                    )),
                Action::make('refund')
                    ->label('Refund')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('notes')
                            ->label('Internal notes')
                            ->required()
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManagePayments->value) ?? false)
                    ->visible(fn (PaymentTransaction $record): bool => self::hasStatus($record, [
                        PaymentTransaction::STATUS_APPROVED,
                        PaymentTransaction::STATUS_PARTIAL,
                        PaymentTransaction::STATUS_ADJUSTED,
                    ]))
                    ->action(fn (array $data, PaymentTransaction $record, ReviewOfflinePayment $reviewOfflinePayment): null => self::review(
                        $record,
                        $reviewOfflinePayment,
                        'refund',
                        $data,
                        'Payment refunded',
                    )),
                Action::make('adjust')
                    ->label('Adjust')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('info')
                    ->schema([
                        TextInput::make('amount')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Textarea::make('notes')
                            ->label('Internal notes')
                            ->required()
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManagePayments->value) ?? false)
                    ->visible(fn (PaymentTransaction $record): bool => self::hasStatus($record, [
                        PaymentTransaction::STATUS_APPROVED,
                        PaymentTransaction::STATUS_PARTIAL,
                    ]))
                    ->action(fn (array $data, PaymentTransaction $record, ReviewOfflinePayment $reviewOfflinePayment): null => self::review(
                        $record,
                        $reviewOfflinePayment,
                        'adjust',
                        $data,
                        'Payment adjusted',
                    )),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @param  array<int, string>  $statuses
     */
    private static function hasStatus(PaymentTransaction $paymentTransaction, array $statuses): bool
    {
        return in_array($paymentTransaction->status, $statuses, true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function review(
        PaymentTransaction $paymentTransaction,
        ReviewOfflinePayment $reviewOfflinePayment,
        string $outcome,
        array $data,
        string $notificationTitle,
    ): null {
        $reviewer = Auth::user();

        abort_unless($reviewer instanceof User, 403);

        $reviewOfflinePayment->handle($paymentTransaction, $reviewer, $outcome, $data);

        Notification::make()
            ->title($notificationTitle)
            ->success()
            ->send();

        return null;
    }
}
