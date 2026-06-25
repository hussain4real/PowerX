<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Actions\PowerX\ConvertLeadToEnrollment;
use App\Actions\PowerX\CreateCommunicationFromTemplate;
use App\Actions\PowerX\RecordLeadActivity;
use App\Enums\PowerXPermission;
use App\Models\CoursePackage;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('team.name')
                    ->searchable(),
                TextColumn::make('owner.name')
                    ->searchable(),
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('course.title')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('source')
                    ->searchable(),
                TextColumn::make('campaign')
                    ->searchable(),
                TextColumn::make('metadata.channel_group')
                    ->label('Channel group')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('metadata.referral.name')
                    ->label('Referral')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('metadata.ai_assistant.next_action')
                    ->label('AI next action')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('course_interest')
                    ->searchable(),
                TextColumn::make('follow_up_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('outcome')
                    ->searchable(),
                TextColumn::make('converted_at')
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
                    ->options(Lead::statusOptions()),
                SelectFilter::make('source')
                    ->options(Lead::sourceOptions()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('logContact')
                    ->label('Log contact')
                    ->icon(Heroicon::OutlinedPhone)
                    ->schema([
                        Select::make('channel')
                            ->options([
                                'phone' => 'Phone',
                                'email' => 'Email',
                                'whatsapp' => 'WhatsApp',
                                'walk-in' => 'Walk-in',
                            ])
                            ->required()
                            ->native(false),
                        DateTimePicker::make('follow_up_at')
                            ->label('Next follow-up'),
                        Textarea::make('notes')
                            ->required()
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManageLeads->value) ?? false)
                    ->action(function (array $data, Lead $record, RecordLeadActivity $recordLeadActivity): void {
                        $actor = Auth::user();

                        abort_unless($actor instanceof User, 403);

                        $recordLeadActivity->handle(
                            lead: $record,
                            actor: $actor,
                            type: LeadActivity::TYPE_CONTACT_LOGGED,
                            title: __('Contact logged.'),
                            notes: $data['notes'],
                            nextStatus: Lead::STATUS_CONTACTED,
                            followUpAt: filled($data['follow_up_at'] ?? null) ? Carbon::parse($data['follow_up_at']) : null,
                            channel: $data['channel'],
                        );

                        Notification::make()->title('Contact logged')->success()->send();
                    }),
                Action::make('qualify')
                    ->label('Qualify')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->schema([
                        Textarea::make('notes')
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManageLeads->value) ?? false)
                    ->visible(fn (Lead $record): bool => $record->status !== Lead::STATUS_QUALIFIED)
                    ->action(function (array $data, Lead $record, RecordLeadActivity $recordLeadActivity): void {
                        $actor = Auth::user();

                        abort_unless($actor instanceof User, 403);

                        $recordLeadActivity->handle(
                            lead: $record,
                            actor: $actor,
                            type: LeadActivity::TYPE_QUALIFIED,
                            title: __('Lead qualified.'),
                            notes: $data['notes'] ?? null,
                            nextStatus: Lead::STATUS_QUALIFIED,
                            outcome: 'interested',
                        );

                        Notification::make()->title('Lead qualified')->success()->send();
                    }),
                Action::make('sendQuotation')
                    ->label('Send quotation')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        DateTimePicker::make('follow_up_at')
                            ->label('Quotation follow-up'),
                        Textarea::make('notes')
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManageLeads->value) ?? false)
                    ->action(function (array $data, Lead $record, RecordLeadActivity $recordLeadActivity, CreateCommunicationFromTemplate $createCommunicationFromTemplate): void {
                        $actor = Auth::user();

                        abort_unless($actor instanceof User, 403);

                        $activity = $recordLeadActivity->handle(
                            lead: $record,
                            actor: $actor,
                            type: LeadActivity::TYPE_QUOTATION_SENT,
                            title: __('Quotation follow-up drafted.'),
                            notes: $data['notes'] ?? null,
                            nextStatus: Lead::STATUS_QUOTATION_SENT,
                            followUpAt: filled($data['follow_up_at'] ?? null) ? Carbon::parse($data['follow_up_at']) : null,
                            metadata: ['quotation_stage' => 'drafted'],
                        );

                        $createCommunicationFromTemplate->handle('lead_follow_up', [
                            'lead_name' => $record->name,
                            'course_title' => $record->course?->title ?? $record->course_interest ?? __('PowerX training'),
                            'recipient_phone' => $record->phone,
                        ], [
                            'team_id' => $record->team_id,
                            'lead_id' => $record->id,
                            'company_id' => $record->company_id,
                            'status' => 'draft',
                            'metadata' => [
                                'lead_activity_id' => $activity->id,
                                'stage' => 'quotation_sent',
                            ],
                        ]);

                        Notification::make()->title('Quotation follow-up drafted')->success()->send();
                    }),
                Action::make('markNotResponsive')
                    ->label('Not responsive')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManageLeads->value) ?? false)
                    ->action(function (Lead $record, RecordLeadActivity $recordLeadActivity): void {
                        $actor = Auth::user();

                        abort_unless($actor instanceof User, 403);

                        $recordLeadActivity->handle(
                            lead: $record,
                            actor: $actor,
                            type: LeadActivity::TYPE_STATUS_CHANGED,
                            title: __('Lead marked not responsive.'),
                            nextStatus: Lead::STATUS_NOT_RESPONSIVE,
                            outcome: 'no_response',
                        );

                        Notification::make()->title('Lead marked not responsive')->success()->send();
                    }),
                Action::make('markLost')
                    ->label('Mark lost')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->schema([
                        Textarea::make('notes')
                            ->required()
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManageLeads->value) ?? false)
                    ->action(function (array $data, Lead $record, RecordLeadActivity $recordLeadActivity): void {
                        $actor = Auth::user();

                        abort_unless($actor instanceof User, 403);

                        $recordLeadActivity->handle(
                            lead: $record,
                            actor: $actor,
                            type: LeadActivity::TYPE_STATUS_CHANGED,
                            title: __('Lead marked lost.'),
                            notes: $data['notes'],
                            nextStatus: Lead::STATUS_LOST,
                            outcome: 'lost',
                        );

                        Notification::make()->title('Lead marked lost')->success()->send();
                    }),
                Action::make('convertToEnrollment')
                    ->label('Convert')
                    ->icon(Heroicon::OutlinedArrowRightCircle)
                    ->color('success')
                    ->schema([
                        Select::make('course_package_id')
                            ->label('Package')
                            ->options(fn (Lead $record): array => $record->course
                                ? CoursePackage::query()
                                    ->whereBelongsTo($record->course)
                                    ->active()
                                    ->orderBy('price')
                                    ->pluck('name', 'id')
                                    ->all()
                                : [])
                            ->searchable()
                            ->native(false),
                        Textarea::make('notes')
                            ->autosize(),
                    ])
                    ->authorize(fn (): bool => Auth::user()?->can(PowerXPermission::ManageRegistrations->value) ?? false)
                    ->visible(fn (Lead $record): bool => $record->course_id !== null && ! in_array($record->status, [Lead::STATUS_ENROLLED, Lead::STATUS_WON], true))
                    ->action(function (array $data, Lead $record, ConvertLeadToEnrollment $convertLeadToEnrollment): void {
                        $actor = Auth::user();

                        abort_unless($actor instanceof User, 403);

                        $package = filled($data['course_package_id'] ?? null)
                            ? CoursePackage::query()->findOrFail($data['course_package_id'])
                            : null;

                        $convertLeadToEnrollment->handle($record, $actor, $package, $data['notes'] ?? null);

                        Notification::make()->title('Lead converted to enrollment')->success()->send();
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
