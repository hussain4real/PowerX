<?php

namespace App\Actions\PowerX;

use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class ResolveStudentLessonAccess
{
    public function canViewLesson(User $user, Team $team, Enrollment $enrollment, Lesson $lesson): bool
    {
        return $this->enrollmentBelongsToStudent($user, $team, $enrollment)
            && $this->lessonBelongsToEnrollment($lesson, $enrollment)
            && ($this->hasPaidAccess($enrollment) || $this->hasPreviewAccess($enrollment, $lesson));
    }

    public function canUpdateProgress(User $user, Team $team, Enrollment $enrollment, Lesson $lesson): bool
    {
        return $this->enrollmentBelongsToStudent($user, $team, $enrollment)
            && $this->lessonBelongsToEnrollment($lesson, $enrollment)
            && $this->hasPaidAccess($enrollment);
    }

    public function hasPaidAccess(Enrollment $enrollment): bool
    {
        return $enrollment->payment_status === 'paid'
            && in_array($enrollment->status, [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_COMPLETED], true)
            && ! $this->dateTimeIsFuture($enrollment->access_starts_at)
            && ! $this->dateTimeIsPast($enrollment->access_expires_at);
    }

    public function hasPreviewAccess(Enrollment $enrollment, Lesson $lesson): bool
    {
        $enrollment->loadMissing('coursePackage');
        $coursePackage = $this->coursePackage($enrollment);
        $allowsFreePreview = $coursePackage !== null && $coursePackage->allows_free_preview;

        return $lesson->is_preview
            && $allowsFreePreview
            && ! in_array($enrollment->status, [Enrollment::STATUS_REJECTED, Enrollment::STATUS_CANCELLED], true);
    }

    public function enrollmentBelongsToStudent(User $user, Team $team, Enrollment $enrollment): bool
    {
        return Enrollment::query()
            ->whereKey($enrollment->getKey())
            ->whereBelongsTo($team)
            ->whereHas('studentProfile', fn (Builder $query) => $query
                ->whereBelongsTo($user)
                ->whereBelongsTo($team))
            ->exists();
    }

    public function lessonBelongsToEnrollment(Lesson $lesson, Enrollment $enrollment): bool
    {
        return Lesson::query()
            ->whereKey($lesson->getKey())
            ->active()
            ->whereHas('courseModule', fn (Builder $query) => $query
                ->where('is_active', true)
                ->where('course_id', $enrollment->course_id))
            ->exists();
    }

    private function dateTimeIsFuture(mixed $value): bool
    {
        return $value instanceof CarbonInterface && $value->isFuture();
    }

    private function dateTimeIsPast(mixed $value): bool
    {
        return $value instanceof CarbonInterface && $value->isPast();
    }

    private function coursePackage(Enrollment $enrollment): ?CoursePackage
    {
        if ($enrollment->course_package_id === null) {
            return null;
        }

        return $enrollment->coursePackage;
    }
}
