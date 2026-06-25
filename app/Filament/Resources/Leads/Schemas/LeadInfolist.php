<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\Lead;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('team.name')
                    ->label('Team')
                    ->placeholder('-'),
                TextEntry::make('owner.name')
                    ->label('Owner')
                    ->placeholder('-'),
                TextEntry::make('company.name')
                    ->label('Company')
                    ->placeholder('-'),
                TextEntry::make('course.title')
                    ->label('Course')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('source')
                    ->placeholder('-'),
                TextEntry::make('campaign')
                    ->placeholder('-'),
                TextEntry::make('metadata.channel_group')
                    ->label('Channel group')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('course_interest')
                    ->placeholder('-'),
                TextEntry::make('metadata.referral.name')
                    ->label('Referral')
                    ->placeholder('-'),
                TextEntry::make('metadata.campaign_cost')
                    ->label('Campaign cost')
                    ->placeholder('-'),
                TextEntry::make('metadata.ai_assistant.handoff_summary')
                    ->label('AI handoff summary')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('metadata.ai_assistant.next_action')
                    ->label('AI next action')
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('follow_up_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('outcome')
                    ->placeholder('-'),
                TextEntry::make('converted_at')
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
                    ->visible(fn (Lead $record): bool => $record->trashed()),
            ]);
    }
}
