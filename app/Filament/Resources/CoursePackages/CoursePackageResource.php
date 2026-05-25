<?php

namespace App\Filament\Resources\CoursePackages;

use App\Filament\PowerXResource;
use App\Filament\Resources\CoursePackages\Pages\CreateCoursePackage;
use App\Filament\Resources\CoursePackages\Pages\EditCoursePackage;
use App\Filament\Resources\CoursePackages\Pages\ListCoursePackages;
use App\Filament\Resources\CoursePackages\Pages\ViewCoursePackage;
use App\Filament\Resources\CoursePackages\Schemas\CoursePackageForm;
use App\Filament\Resources\CoursePackages\Schemas\CoursePackageInfolist;
use App\Filament\Resources\CoursePackages\Tables\CoursePackagesTable;
use App\Models\CoursePackage;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CoursePackageResource extends PowerXResource
{
    protected static ?string $model = CoursePackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CoursePackageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CoursePackageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoursePackagesTable::configure($table);
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
            'index' => ListCoursePackages::route('/'),
            'create' => CreateCoursePackage::route('/create'),
            'view' => ViewCoursePackage::route('/{record}'),
            'edit' => EditCoursePackage::route('/{record}/edit'),
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
