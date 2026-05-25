<?php

namespace App\Filament\Resources\AttendanceRecords\Schemas;

use App\Models\AttendanceRecord;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceRecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('team.name')
                    ->label('Team')
                    ->placeholder('-'),
                TextEntry::make('trainingSession.title')
                    ->label('Training session'),
                TextEntry::make('enrollment.id')
                    ->label('Enrollment'),
                TextEntry::make('markedBy.name')
                    ->label('Marked by')
                    ->placeholder('-'),
                TextEntry::make('assessedBy.name')
                    ->label('Assessed by')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('attended_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('practical_outcome')
                    ->placeholder('-'),
                TextEntry::make('practical_score')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('practical_comments')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('assessed_at')
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
                    ->visible(fn (AttendanceRecord $record): bool => $record->trashed()),
            ]);
    }
}
