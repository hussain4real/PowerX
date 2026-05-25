<?php

namespace App\Filament\Resources\LessonProgress;

use App\Filament\PowerXResource;
use App\Filament\Resources\LessonProgress\Pages\CreateLessonProgress;
use App\Filament\Resources\LessonProgress\Pages\EditLessonProgress;
use App\Filament\Resources\LessonProgress\Pages\ListLessonProgress;
use App\Filament\Resources\LessonProgress\Pages\ViewLessonProgress;
use App\Filament\Resources\LessonProgress\Schemas\LessonProgressForm;
use App\Filament\Resources\LessonProgress\Schemas\LessonProgressInfolist;
use App\Filament\Resources\LessonProgress\Tables\LessonProgressTable;
use App\Models\LessonProgress;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LessonProgressResource extends PowerXResource
{
    protected static ?string $model = LessonProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LessonProgressForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LessonProgressInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LessonProgressTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLessonProgress::route('/'),
            'create' => CreateLessonProgress::route('/create'),
            'view' => ViewLessonProgress::route('/{record}'),
            'edit' => EditLessonProgress::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
