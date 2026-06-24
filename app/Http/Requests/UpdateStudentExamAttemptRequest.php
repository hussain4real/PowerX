<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use App\Models\ExamAttempt;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentExamAttemptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $team = $user?->currentTeam;
        $examAttempt = $this->route('examAttempt');

        if (! $user instanceof User || ! $team instanceof Team || ! $examAttempt instanceof ExamAttempt) {
            return false;
        }

        if (! $user->canViewStudentPortal($team) || $examAttempt->submitted_at !== null) {
            return false;
        }

        $examAttempt->loadMissing('exam');

        if ($examAttempt->started_at && $examAttempt->started_at->copy()->addMinutes($examAttempt->exam->duration_minutes + 5)->isPast()) {
            return false;
        }

        return Enrollment::query()
            ->whereKey($examAttempt->enrollment_id)
            ->whereBelongsTo($team)
            ->where('payment_status', 'paid')
            ->whereIn('status', [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_COMPLETED])
            ->where(fn (Builder $query) => $query
                ->whereNull('access_starts_at')
                ->orWhere('access_starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query
                ->whereNull('access_expires_at')
                ->orWhere('access_expires_at', '>=', now()))
            ->whereHas('studentProfile', fn (Builder $query) => $query
                ->whereBelongsTo($user)
                ->whereBelongsTo($team))
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
            'answers' => ['nullable', 'array'],
            'answers.*.question_id' => ['required_with:answers', 'integer', 'min:1'],
            'answers.*.answer' => ['nullable'],
        ];
    }
}
