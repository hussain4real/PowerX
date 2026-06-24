<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Actions\PowerX\RecordAssessmentConfigurationChange;
use App\Filament\Resources\Exams\ExamResource;
use App\Models\Exam;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditExam extends EditRecord
{
    protected static string $resource = ExamResource::class;

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
        if (! $this->record instanceof Exam) {
            return;
        }

        $actor = Auth::user();

        app(RecordAssessmentConfigurationChange::class)->handle(
            subject: $this->record,
            actor: $actor instanceof User ? $actor : null,
            before: $this->assessmentConfigurationBefore,
            after: $this->assessmentConfigurationSnapshot(),
            metadata: ['configuration_scope' => 'exam_attempt_rules'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function assessmentConfigurationSnapshot(): array
    {
        if (! $this->record instanceof Exam) {
            return [];
        }

        return [
            'duration_minutes' => $this->record->duration_minutes,
            'pass_mark' => $this->record->pass_mark,
            'max_attempts' => $this->record->max_attempts,
            'question_count' => $this->record->question_count,
            'randomize_questions' => $this->record->randomize_questions,
            'is_active' => $this->record->is_active,
            'metadata' => $this->record->metadata,
        ];
    }
}
