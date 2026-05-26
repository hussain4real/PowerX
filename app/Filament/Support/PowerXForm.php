<?php

namespace App\Filament\Support;

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\StudentProfile;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PowerXForm
{
    /**
     * @return array<string, string>
     */
    public static function currencyOptions(): array
    {
        return [
            'QAR' => 'QAR',
        ];
    }

    public static function currentTeamId(): ?int
    {
        return Auth::user()?->current_team_id;
    }

    public static function teamId(): Hidden
    {
        return Hidden::make('team_id')
            ->default(fn (): ?int => self::currentTeamId());
    }

    public static function metadataSection(): Section
    {
        return Section::make('Advanced metadata')
            ->description('Optional structured data for imports, integrations, or support notes.')
            ->schema([
                KeyValue::make('metadata')
                    ->keyLabel('Key')
                    ->valueLabel('Value')
                    ->keyPlaceholder('source')
                    ->valuePlaceholder('system')
                    ->columnSpanFull(),
            ])
            ->collapsible()
            ->collapsed()
            ->columnSpanFull();
    }

    /**
     * @param  array<int, string>  $acceptedFileTypes
     */
    public static function mediaUpload(
        string $name,
        string $collection,
        array $acceptedFileTypes,
        int $maxSize = 10240,
        bool $multiple = false,
        ?int $maxFiles = null,
    ): SpatieMediaLibraryFileUpload {
        $upload = SpatieMediaLibraryFileUpload::make($name)
            ->collection($collection)
            ->acceptedFileTypes($acceptedFileTypes)
            ->maxSize($maxSize)
            ->visibility('private')
            ->downloadable()
            ->openable();

        if ($multiple) {
            $upload
                ->multiple()
                ->reorderable();
        }

        if ($maxFiles !== null) {
            $upload->maxFiles($maxFiles);
        }

        return $upload;
    }

    public static function imageUpload(string $name, string $collection, int $maxSize = 4096): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make($name)
            ->collection($collection)
            ->image()
            ->acceptedFileTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
            ])
            ->maxSize($maxSize)
            ->visibility('private')
            ->downloadable()
            ->openable();
    }

    public static function pdfUpload(string $name, string $collection, int $maxSize = 10240): SpatieMediaLibraryFileUpload
    {
        return self::mediaUpload($name, $collection, [
            'application/pdf',
        ], $maxSize);
    }

    public static function readOnlyPdfUpload(string $name, string $collection): SpatieMediaLibraryFileUpload
    {
        return self::pdfUpload($name, $collection)
            ->disabled()
            ->deletable(false);
    }

    public static function currencySelect(): Select
    {
        return Select::make('currency')
            ->options(self::currencyOptions())
            ->required()
            ->default('QAR')
            ->native(false);
    }

    public static function moneyInput(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->minValue(0)
            ->step('0.01')
            ->prefix('QAR');
    }

    public static function integerInput(string $name): TextInput
    {
        return TextInput::make($name)
            ->integer()
            ->minValue(0);
    }

    public static function percentageInput(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->minValue(0)
            ->maxValue(100)
            ->suffix('%');
    }

    public static function teamScoped(Builder $query): Builder
    {
        $teamId = self::currentTeamId();

        if ($teamId === null) {
            return $query;
        }

        return $query->where($query->qualifyColumn('team_id'), $teamId);
    }

    public static function relationshipSelect(
        string $name,
        string $relationship,
        string $titleAttribute,
        bool $teamScoped = true,
    ): Select {
        return Select::make($name)
            ->relationship(
                $relationship,
                $titleAttribute,
                modifyQueryUsing: $teamScoped
                    ? fn (Builder $query): Builder => self::teamScoped($query)
                    : null,
            )
            ->searchable();
    }

    public static function studentProfileSelect(string $name = 'student_profile_id'): Select
    {
        return self::relationshipSelect($name, 'studentProfile', 'full_name')
            ->getOptionLabelFromRecordUsing(fn (StudentProfile $record): string => self::studentProfileLabel($record));
    }

    public static function enrollmentSelect(string $name = 'enrollment_id'): Select
    {
        return self::relationshipSelect($name, 'enrollment', 'id')
            ->getOptionLabelFromRecordUsing(fn (Enrollment $record): string => self::enrollmentLabel($record));
    }

    public static function invoiceSelect(string $name = 'invoice_id'): Select
    {
        return self::relationshipSelect($name, 'invoice', 'number')
            ->getOptionLabelFromRecordUsing(fn (Invoice $record): string => self::invoiceLabel($record));
    }

    private static function studentProfileLabel(StudentProfile $record): string
    {
        return collect([$record->full_name, $record->email, $record->mobile])
            ->filter()
            ->implode(' - ');
    }

    private static function enrollmentLabel(Enrollment $record): string
    {
        $record->loadMissing(['studentProfile', 'course']);

        return collect([
            "#{$record->id}",
            $record->studentProfile?->full_name,
            $record->course?->title,
            $record->status,
        ])
            ->filter()
            ->implode(' - ');
    }

    private static function invoiceLabel(Invoice $record): string
    {
        $record->loadMissing(['studentProfile', 'company']);

        return collect([
            $record->number,
            $record->studentProfile?->full_name ?? $record->company?->name,
            $record->status,
            $record->total ? "QAR {$record->total}" : null,
        ])
            ->filter()
            ->implode(' - ');
    }
}
