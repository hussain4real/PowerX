<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
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

        return Enrollment::query()
            ->whereKey($enrollment->getKey())
            ->whereBelongsTo($team)
            ->where('payment_status', 'paid')
            ->whereIn('status', ['active', 'completed'])
            ->where(fn (Builder $query) => $query
                ->whereNull('access_starts_at')
                ->orWhere('access_starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query
                ->whereNull('access_expires_at')
                ->orWhere('access_expires_at', '>=', now()))
            ->whereHas('studentProfile', fn (Builder $query) => $query
                ->whereBelongsTo($user)
                ->whereBelongsTo($team))
            ->whereHas('course.modules.lessons', fn (Builder $query) => $query
                ->whereKey($lesson->getKey())
                ->active()
                ->whereHas('courseModule', fn (Builder $query) => $query->active()))
            ->exists();
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
        ];
    }
}
