<?php

namespace App\Filament\Resources\Communications\Schemas;

use App\Models\Communication;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CommunicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('team.name')
                    ->label('Team')
                    ->placeholder('-'),
                TextEntry::make('lead.name')
                    ->label('Lead')
                    ->placeholder('-'),
                TextEntry::make('studentProfile.id')
                    ->label('Student profile')
                    ->placeholder('-'),
                TextEntry::make('company.name')
                    ->label('Company')
                    ->placeholder('-'),
                TextEntry::make('user.name')
                    ->label('User')
                    ->placeholder('-'),
                TextEntry::make('channel'),
                TextEntry::make('template_key')
                    ->placeholder('-'),
                TextEntry::make('subject')
                    ->placeholder('-'),
                TextEntry::make('message')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Communication::statusOptions()[$state] ?? str($state)->headline()->toString()),
                TextEntry::make('scheduled_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('queued_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('sent_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('delivered_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('failed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('retry_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('retry_count')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('failure_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('opted_out_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('opt_out_reason')
                    ->placeholder('-'),
                TextEntry::make('metadata.provider.name')
                    ->label('Provider')
                    ->placeholder('-'),
                TextEntry::make('metadata.provider.status')
                    ->label('Provider status')
                    ->placeholder('-'),
                TextEntry::make('metadata.provider.message_id')
                    ->label('Provider message')
                    ->placeholder('-'),
                TextEntry::make('metadata.provider.last_webhook.status')
                    ->label('Last webhook')
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
                    ->visible(fn (Communication $record): bool => $record->trashed()),
            ]);
    }
}
