<?php

namespace App\Actions\PowerX;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Arr;

class BuildStudentLessonViewer
{
    public function __construct(private BuildStudentPortal $buildStudentPortal) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user, Team $team, Enrollment $enrollment, Lesson $lesson): array
    {
        $portal = $this->buildStudentPortal->handle($user, $team);
        $enrollmentPayload = $this->findEnrollmentPayload($portal['enrollments'] ?? [], $enrollment);

        abort_unless(is_array($enrollmentPayload), 404);

        $lessons = $this->lessonPayloads($enrollmentPayload['modules'] ?? []);
        $currentIndex = $this->lessonIndex($lessons, $lesson);

        abort_unless($currentIndex !== false, 404);

        return [
            ...Arr::only($portal, ['profile', 'summary', 'courseCatalog']),
            'enrollment' => $enrollmentPayload,
            'lesson' => $lessons[$currentIndex],
            'previousLesson' => $currentIndex > 0 ? $lessons[$currentIndex - 1] : null,
            'nextLesson' => $currentIndex < count($lessons) - 1 ? $lessons[$currentIndex + 1] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findEnrollmentPayload(mixed $enrollments, Enrollment $enrollment): ?array
    {
        if (! is_array($enrollments)) {
            return null;
        }

        foreach ($enrollments as $candidate) {
            if (is_array($candidate) && ($candidate['id'] ?? null) === $enrollment->id) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lessonPayloads(mixed $modules): array
    {
        if (! is_array($modules)) {
            return [];
        }

        $lessons = [];

        foreach ($modules as $module) {
            if (! is_array($module) || ! is_array($module['lessons'] ?? null)) {
                continue;
            }

            foreach ($module['lessons'] as $lesson) {
                if (is_array($lesson)) {
                    $lessons[] = $lesson;
                }
            }
        }

        return $lessons;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lessons
     */
    private function lessonIndex(array $lessons, Lesson $lesson): int|false
    {
        foreach ($lessons as $index => $lessonPayload) {
            if (($lessonPayload['id'] ?? null) === $lesson->id) {
                return $index;
            }
        }

        return false;
    }
}
