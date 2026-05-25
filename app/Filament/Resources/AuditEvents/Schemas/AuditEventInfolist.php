<?php

namespace App\Filament\Resources\AuditEvents\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AuditEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('action')
                    ->badge(),
                TextEntry::make('summary')
                    ->columnSpanFull(),
                TextEntry::make('team.name')
                    ->label('Team'),
                TextEntry::make('actor.name')
                    ->label('Actor'),
                TextEntry::make('subject_type')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? class_basename($state) : null),
                TextEntry::make('subject_id'),
                TextEntry::make('ip_address'),
                TextEntry::make('user_agent')
                    ->columnSpanFull(),
                KeyValueEntry::make('before')
                    ->columnSpanFull(),
                KeyValueEntry::make('after')
                    ->columnSpanFull(),
                KeyValueEntry::make('metadata')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
