<?php

namespace App\Filament\Resources\Enrollments\Tables;

use App\Actions\PowerX\DecideEnrollmentAdmission;
use App\Enums\PowerXPermission;
use App\Models\Enrollment;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('team.name')
                    ->searchable(),
                TextColumn::make('studentProfile.id')
                    ->searchable(),
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('course.title')
                    ->searchable(),
                TextColumn::make('coursePackage.name')
                    ->searchable(),
                TextColumn::make('approvedBy.name')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('payment_status')
                    ->searchable(),
                TextColumn::make('access_starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('access_expires_at')
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
                    ->options(Enrollment::statusOptions()),
                SelectFilter::make('payment_status')
                    ->options(Enrollment::paymentStatusOptions()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approveAdmission')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve enrollment')
                    ->modalDescription('This records an audited admissions approval and drafts the student notification.')
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManageRegistrations->value) ?? false)
                    ->visible(fn (Enrollment $record): bool => in_array($record->status, [
                        Enrollment::STATUS_PENDING,
                        Enrollment::STATUS_REQUEST_MORE_INFORMATION,
                    ], true))
                    ->action(function (Enrollment $record, DecideEnrollmentAdmission $decideEnrollmentAdmission): void {
                        $actor = Auth::user();

                        abort_unless($actor instanceof User, 403);

                        $decideEnrollmentAdmission->handle($record, $actor, DecideEnrollmentAdmission::DECISION_APPROVE);

                        Notification::make()->title('Enrollment approved')->success()->send();
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
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManageRegistrations->value) ?? false)
                    ->visible(fn (Enrollment $record): bool => in_array($record->status, [
                        Enrollment::STATUS_PENDING,
                        Enrollment::STATUS_APPROVED,
                    ], true))
                    ->action(function (array $data, Enrollment $record, DecideEnrollmentAdmission $decideEnrollmentAdmission): void {
                        $actor = Auth::user();

                        abort_unless($actor instanceof User, 403);

                        $decideEnrollmentAdmission->handle($record, $actor, DecideEnrollmentAdmission::DECISION_REQUEST_INFORMATION, $data['notes']);

                        Notification::make()->title('Information requested')->success()->send();
                    }),
                Action::make('rejectAdmission')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Reason')
                            ->required()
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManageRegistrations->value) ?? false)
                    ->visible(fn (Enrollment $record): bool => ! in_array($record->status, [
                        Enrollment::STATUS_REJECTED,
                        Enrollment::STATUS_ACTIVE,
                        Enrollment::STATUS_COMPLETED,
                    ], true))
                    ->action(function (array $data, Enrollment $record, DecideEnrollmentAdmission $decideEnrollmentAdmission): void {
                        $actor = Auth::user();

                        abort_unless($actor instanceof User, 403);

                        $decideEnrollmentAdmission->handle($record, $actor, DecideEnrollmentAdmission::DECISION_REJECT, $data['notes']);

                        Notification::make()->title('Enrollment rejected')->success()->send();
                    }),
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
}
