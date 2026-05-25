<?php

namespace App\Filament\Resources\CoursePackages\Schemas;

use App\Models\CoursePackage;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CoursePackageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('team.name')
                    ->label('Team')
                    ->placeholder('-'),
                TextEntry::make('course.title')
                    ->label('Course'),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('package_type'),
                TextEntry::make('currency'),
                TextEntry::make('price')
                    ->money(),
                TextEntry::make('discount_price')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('validity_days')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('max_exam_attempts')
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('includes_certificate')
                    ->boolean(),
                IconEntry::make('allows_free_preview')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->boolean(),
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
                    ->visible(fn (CoursePackage $record): bool => $record->trashed()),
            ]);
    }
}
