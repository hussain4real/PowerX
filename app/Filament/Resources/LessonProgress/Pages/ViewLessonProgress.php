<?php

namespace App\Filament\Resources\LessonProgress\Pages;

use App\Filament\Resources\LessonProgress\LessonProgressResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLessonProgress extends ViewRecord
{
    protected static string $resource = LessonProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
