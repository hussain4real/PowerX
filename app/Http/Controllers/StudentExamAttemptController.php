<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\BuildStudentExamAttempt;
use App\Actions\PowerX\StartExamAttempt;
use App\Actions\PowerX\SubmitExamAttempt;
use App\Http\Requests\StoreStudentExamAttemptRequest;
use App\Http\Requests\SubmitStudentExamAttemptRequest;
use App\Http\Requests\UpdateStudentExamAttemptRequest;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentExamAttemptController extends Controller
{
    public function store(
        StoreStudentExamAttemptRequest $request,
        string $currentTeam,
        Enrollment $enrollment,
        Exam $exam,
        StartExamAttempt $startExamAttempt,
    ): RedirectResponse {
        $attempt = $startExamAttempt->handle($exam, $enrollment);

        return to_route('student.exam-attempts.show', [
            'current_team' => $currentTeam,
            'examAttempt' => $attempt,
        ]);
    }

    public function show(
        Request $request,
        string $currentTeam,
        ExamAttempt $examAttempt,
        BuildStudentExamAttempt $buildStudentExamAttempt,
    ): Response {
        $this->authorizeStudentAttempt($request, $examAttempt);

        return Inertia::render('Student/ExamAttempt', $buildStudentExamAttempt->handle($examAttempt));
    }

    public function update(
        UpdateStudentExamAttemptRequest $request,
        string $currentTeam,
        ExamAttempt $examAttempt,
    ): RedirectResponse {
        $data = $request->validated();
        $metadata = $examAttempt->metadata ?? [];
        $metadata['draft_answers'] = $data['answers'] ?? [];
        $metadata['draft_saved_at'] = now()->toISOString();

        $examAttempt->update(['metadata' => $metadata]);

        return back();
    }

    public function submit(
        SubmitStudentExamAttemptRequest $request,
        string $currentTeam,
        ExamAttempt $examAttempt,
        SubmitExamAttempt $submitExamAttempt,
    ): RedirectResponse {
        $submitExamAttempt->handle($examAttempt, $request->validated()['answers']);

        return to_route('student.exam-attempts.show', [
            'current_team' => $currentTeam,
            'examAttempt' => $examAttempt,
        ]);
    }

    private function authorizeStudentAttempt(Request $request, ExamAttempt $examAttempt): void
    {
        $user = $request->user();
        $team = $user?->currentTeam;

        abort_unless($user instanceof User && $team instanceof Team && $user->canViewStudentPortal($team), 403);

        $ownsAttempt = ExamAttempt::query()
            ->whereKey($examAttempt->getKey())
            ->whereBelongsTo($team)
            ->whereHas('enrollment', fn (Builder $query) => $query
                ->whereBelongsTo($team)
                ->whereHas('studentProfile', fn (Builder $query) => $query
                    ->whereBelongsTo($user)
                    ->whereBelongsTo($team)))
            ->exists();

        abort_unless($ownsAttempt, 403);
    }
}
