<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\UpdateLessonProgress;
use App\Http\Requests\UpdateStudentLessonProgressRequest;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;

class StudentLessonProgressController extends Controller
{
    public function __invoke(
        UpdateStudentLessonProgressRequest $request,
        string $currentTeam,
        Enrollment $enrollment,
        Lesson $lesson,
        UpdateLessonProgress $updateLessonProgress,
    ): RedirectResponse {
        $updateLessonProgress->handle($enrollment, $lesson, $request->validated());

        return back();
    }
}
