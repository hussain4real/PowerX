<?php

namespace App\Filament\Resources\AuditEvents\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('action')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? class_basename($state) : null)
                    ->searchable(),
                TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->sortable(),
                TextColumn::make('summary')
                    ->searchable()
                    ->limit(80),
                TextColumn::make('ip_address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'payment.approved' => 'Payment approved',
                        'certificate.issued' => 'Certificate issued',
                        'exam.updated' => 'Exam updated',
                        'role.changed' => 'Role changed',
                        'data.exported' => 'Data exported',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
