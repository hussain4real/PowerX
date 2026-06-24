<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Actions\PowerX\RecordAssessmentConfigurationChange;
use App\Filament\Resources\Courses\CourseResource;
use App\Models\Course;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    /**
     * @var array<string, mixed>
     */
    private array $assessmentConfigurationBefore = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->assessmentConfigurationBefore = $this->assessmentConfigurationSnapshot();
    }

    protected function afterSave(): void
    {
        if (! $this->record instanceof Course) {
            return;
        }

        $actor = Auth::user();

        app(RecordAssessmentConfigurationChange::class)->handle(
            subject: $this->record,
            actor: $actor instanceof User ? $actor : null,
            before: $this->assessmentConfigurationBefore,
            after: $this->assessmentConfigurationSnapshot(),
            metadata: ['configuration_scope' => 'course_certificate_requirements'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function assessmentConfigurationSnapshot(): array
    {
        if (! $this->record instanceof Course) {
            return [];
        }

        return [
            'requires_lesson_completion_for_certificate' => $this->record->requires_lesson_completion_for_certificate,
            'requires_exam_pass_for_certificate' => $this->record->requires_exam_pass_for_certificate,
            'requires_attendance_for_certificate' => $this->record->requires_attendance_for_certificate,
            'requires_practical_pass_for_certificate' => $this->record->requires_practical_pass_for_certificate,
        ];
    }
}
