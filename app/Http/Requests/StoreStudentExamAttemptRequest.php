<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentExamAttemptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $team = $user?->currentTeam;
        $enrollment = $this->route('enrollment');
        $exam = $this->route('exam');

        if (! $user instanceof User || ! $team instanceof Team || ! $enrollment instanceof Enrollment || ! $exam instanceof Exam) {
            return false;
        }

        if (! $user->canViewStudentPortal($team) || ! $exam->is_active || (int) $exam->course_id !== (int) $enrollment->course_id) {
            return false;
        }

        return Enrollment::query()
            ->whereKey($enrollment->getKey())
            ->whereBelongsTo($team)
            ->where('course_id', $exam->course_id)
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
        return [];
    }
}
