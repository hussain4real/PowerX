<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Models\Course;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CourseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('team.name')
                    ->label('Team')
                    ->placeholder('-'),
                TextEntry::make('title'),
                TextEntry::make('slug'),
                TextEntry::make('category')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('delivery_mode'),
                TextEntry::make('currency'),
                TextEntry::make('base_price')
                    ->money(),
                TextEntry::make('validity_days')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('summary')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_featured')
                    ->boolean(),
                TextEntry::make('content_revision')
                    ->numeric(),
                TextEntry::make('content_retired_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('replacementCourse.title')
                    ->label('Replacement course')
                    ->placeholder('-'),
                TextEntry::make('content_retirement_note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('metadata')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('published_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Course $record): bool => $record->trashed()),
            ]);
    }
}
