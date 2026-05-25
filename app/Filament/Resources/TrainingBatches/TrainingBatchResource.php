<?php

namespace App\Filament\Resources\TrainingBatches;

use App\Filament\PowerXResource;
use App\Filament\Resources\TrainingBatches\Pages\CreateTrainingBatch;
use App\Filament\Resources\TrainingBatches\Pages\EditTrainingBatch;
use App\Filament\Resources\TrainingBatches\Pages\ListTrainingBatches;
use App\Filament\Resources\TrainingBatches\Pages\ViewTrainingBatch;
use App\Filament\Resources\TrainingBatches\Schemas\TrainingBatchForm;
use App\Filament\Resources\TrainingBatches\Schemas\TrainingBatchInfolist;
use App\Filament\Resources\TrainingBatches\Tables\TrainingBatchesTable;
use App\Models\TrainingBatch;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TrainingBatchResource extends PowerXResource
{
    protected static ?string $model = TrainingBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TrainingBatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TrainingBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrainingBatchesTable::configure($table);
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
            'index' => ListTrainingBatches::route('/'),
            'create' => CreateTrainingBatch::route('/create'),
            'view' => ViewTrainingBatch::route('/{record}'),
            'edit' => EditTrainingBatch::route('/{record}/edit'),
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
