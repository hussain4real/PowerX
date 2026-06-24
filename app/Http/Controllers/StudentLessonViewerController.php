<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\BuildStudentLessonViewer;
use App\Actions\PowerX\ResolveStudentLessonAccess;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentLessonViewerController extends Controller
{
    public function __invoke(
        Request $request,
        string $currentTeam,
        Enrollment $enrollment,
        Lesson $lesson,
        BuildStudentLessonViewer $buildStudentLessonViewer,
        ResolveStudentLessonAccess $resolveStudentLessonAccess,
    ): Response {
        $user = $request->user();
        $team = $user?->currentTeam;

        abort_unless($user instanceof User && $team instanceof Team && $user->canViewStudentPortal($team), 403);
        abort_unless($resolveStudentLessonAccess->canViewLesson($user, $team, $enrollment, $lesson), 403);

        return Inertia::render('Student/LessonViewer', $buildStudentLessonViewer->handle(
            user: $user,
            team: $team,
            enrollment: $enrollment,
            lesson: $lesson,
        ));
    }
}
