<?php

namespace App\Filament\Resources\TrainingBatches\Pages;

use App\Filament\Resources\TrainingBatches\TrainingBatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTrainingBatch extends ViewRecord
{
    protected static string $resource = TrainingBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
