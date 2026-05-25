<?php

namespace App\Filament\Resources\CoursePackages\Pages;

use App\Filament\Resources\CoursePackages\CoursePackageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCoursePackage extends ViewRecord
{
    protected static string $resource = CoursePackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
