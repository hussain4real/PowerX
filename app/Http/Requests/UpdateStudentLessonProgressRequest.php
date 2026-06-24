<?php

namespace App\Http\Requests;

use App\Actions\PowerX\ResolveStudentLessonAccess;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentLessonProgressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $team = $user?->currentTeam;
        $enrollment = $this->route('enrollment');
        $lesson = $this->route('lesson');

        if (! $user instanceof User || ! $team instanceof Team || ! $enrollment instanceof Enrollment || ! $lesson instanceof Lesson) {
            return false;
        }

        if (! $user->canViewStudentPortal($team)) {
            return false;
        }

        return app(ResolveStudentLessonAccess::class)->canUpdateProgress($user, $team, $enrollment, $lesson);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'last_position_seconds' => ['nullable', 'integer', 'min:0'],
            'event' => ['nullable', 'string', 'max:80'],
            'media_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
