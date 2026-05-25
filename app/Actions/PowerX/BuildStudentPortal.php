<?php

namespace App\Actions\PowerX;

use App\Models\Enrollment;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;

class BuildStudentPortal
{
    /**
     * @return array<string, mixed>
     */
    public function handle(User $user, Team $team): array
    {
        $profile = StudentProfile::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($team)
            ->with([
                'enrollments' => fn ($query) => $query->whereBelongsTo($team)->latest('id'),
                'enrollments.course.exams' => fn ($query) => $query->active()->orderBy('title'),
                'enrollments.course.modules' => fn ($query) => $query->active()->orderBy('sort_order'),
                'enrollments.course.modules.lessons' => fn ($query) => $query->active()->orderBy('sort_order'),
                'enrollments.coursePackage',
                'enrollments.lessonProgress.lesson',
                'enrollments.examAttempts.exam',
                'enrollments.certificates',
                'enrollments.attendanceRecords.trainingSession.trainingBatch.instructor',
                'enrollments.invoices',
                'enrollments.paymentTransactions',
            ])
            ->first();

        if (! $profile) {
            return $this->emptyPayload();
        }

        $enrollments = $profile->enrollments;
        $portalEnrollments = $enrollments->map(fn (Enrollment $enrollment): array => $this->enrollmentPayload($enrollment));
        $nextSession = $portalEnrollments
            ->flatMap(fn (array $enrollment): array => $enrollment['schedule'])
            ->filter(fn (array $session): bool => $session['startsAt'] !== null && $session['startsAt'] >= now()->toISOString())
            ->sortBy('startsAt')
            ->first();

        return [
            'profile' => [
                'id' => $profile->id,
                'fullName' => $profile->full_name,
                'email' => $profile->email,
                'mobile' => $profile->mobile,
                'profession' => $profile->profession,
                'qatarLocation' => $profile->qatar_location,
                'preferredSchedule' => $profile->preferred_schedule,
                'documentStatus' => $profile->document_status,
            ],
            'summary' => [
                'enrolledCourses' => $enrollments->count(),
                'activeEnrollments' => $enrollments->whereIn('status', ['active', 'completed'])->count(),
                'pendingPayments' => $enrollments->where('payment_status', '!=', 'paid')->count(),
                'issuedCertificates' => $enrollments->flatMap->certificates->where('status', 'issued')->count(),
                'averageProgress' => (int) round($portalEnrollments->avg('progress.percentage') ?? 0),
                'nextSessionLabel' => $nextSession['title'] ?? 'No upcoming assigned session',
            ],
            'enrollments' => $portalEnrollments->values()->all(),
        ];
    }

    /**
     * @return array{profile: null, summary: array<string, int|string>, enrollments: array<int, mixed>}
     */
    private function emptyPayload(): array
    {
        return [
            'profile' => null,
            'summary' => [
                'enrolledCourses' => 0,
                'activeEnrollments' => 0,
                'pendingPayments' => 0,
                'issuedCertificates' => 0,
                'averageProgress' => 0,
                'nextSessionLabel' => 'Create a student profile to begin.',
            ],
            'enrollments' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function enrollmentPayload(Enrollment $enrollment): array
    {
        $accessStatus = $this->accessStatus($enrollment);
        $progress = $this->progressPayload($enrollment);

        return [
            'id' => $enrollment->id,
            'status' => $enrollment->status,
            'paymentStatus' => $enrollment->payment_status,
            'accessStatus' => $accessStatus,
            'hasPaidAccess' => $accessStatus === 'open',
            'accessStartsAt' => $enrollment->access_starts_at?->toISOString(),
            'accessExpiresAt' => $enrollment->access_expires_at?->toISOString(),
            'course' => [
                'id' => $enrollment->course->id,
                'title' => $enrollment->course->title,
                'slug' => $enrollment->course->slug,
                'category' => $enrollment->course->category,
                'deliveryMode' => $enrollment->course->delivery_mode,
                'url' => route('courses.show', ['course' => $enrollment->course]),
            ],
            'package' => [
                'name' => $enrollment->coursePackage?->name,
                'packageType' => $enrollment->coursePackage?->package_type,
                'validityDays' => $enrollment->coursePackage?->validity_days,
                'includesCertificate' => $enrollment->coursePackage?->includes_certificate,
            ],
            'progress' => $progress,
            'modules' => $this->modulesPayload($enrollment, $accessStatus === 'open'),
            'schedule' => $this->schedulePayload($enrollment),
            'exams' => $this->examsPayload($enrollment, $accessStatus === 'open'),
            'certificates' => $this->certificatesPayload($enrollment),
            'finance' => [
                'invoices' => $enrollment->invoices
                    ->sortByDesc('issued_at')
                    ->map(fn ($invoice): array => [
                        'id' => $invoice->id,
                        'number' => $invoice->number,
                        'type' => $invoice->type,
                        'status' => $invoice->status,
                        'currency' => $invoice->currency,
                        'total' => (float) $invoice->total,
                        'dueAt' => $invoice->due_at?->toISOString(),
                    ])
                    ->values()
                    ->all(),
                'payments' => $enrollment->paymentTransactions
                    ->sortByDesc('paid_at')
                    ->map(fn ($payment): array => [
                        'id' => $payment->id,
                        'method' => $payment->method,
                        'reference' => $payment->reference,
                        'status' => $payment->status,
                        'currency' => $payment->currency,
                        'amount' => (float) $payment->amount,
                        'paidAt' => $payment->paid_at?->toISOString(),
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    private function accessStatus(Enrollment $enrollment): string
    {
        if ($enrollment->payment_status !== 'paid') {
            return 'payment_pending';
        }

        if (! in_array($enrollment->status, ['active', 'completed'], true)) {
            return 'enrollment_pending';
        }

        if ($enrollment->access_expires_at?->isPast()) {
            return 'expired';
        }

        return 'open';
    }

    /**
     * @return array{completedLessons: int, totalLessons: int, percentage: int}
     */
    private function progressPayload(Enrollment $enrollment): array
    {
        $totalLessons = $enrollment->course->modules->flatMap->lessons->count();
        $completedLessons = $enrollment->lessonProgress->whereNotNull('completed_at')->count();

        return [
            'completedLessons' => $completedLessons,
            'totalLessons' => $totalLessons,
            'percentage' => $totalLessons > 0 ? (int) round(($completedLessons / $totalLessons) * 100) : 0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function modulesPayload(Enrollment $enrollment, bool $hasPaidAccess): array
    {
        $progressByLesson = $enrollment->lessonProgress->keyBy('lesson_id');

        return $enrollment->course->modules
            ->map(fn ($module): array => [
                'id' => $module->id,
                'title' => $module->title,
                'summary' => $module->summary,
                'lessons' => $module->lessons
                    ->map(function ($lesson) use ($progressByLesson, $hasPaidAccess): array {
                        $progress = $progressByLesson->get($lesson->id);

                        return [
                            'id' => $lesson->id,
                            'title' => $lesson->title,
                            'lessonType' => $lesson->lesson_type,
                            'durationMinutes' => $lesson->duration_minutes,
                            'isPreview' => $lesson->is_preview,
                            'isLocked' => ! $hasPaidAccess && ! $lesson->is_preview,
                            'progressPercentage' => (int) ($progress?->progress_percentage ?? 0),
                            'isCompleted' => $progress?->completed_at !== null,
                        ];
                    })
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function schedulePayload(Enrollment $enrollment): array
    {
        return $enrollment->attendanceRecords
            ->sortBy(fn ($attendance): ?string => $attendance->trainingSession?->starts_at?->toISOString())
            ->map(fn ($attendance): array => [
                'id' => $attendance->trainingSession->id,
                'title' => $attendance->trainingSession->title,
                'sessionType' => $attendance->trainingSession->session_type,
                'venue' => $attendance->trainingSession->venue,
                'status' => $attendance->status,
                'practicalOutcome' => $attendance->practical_outcome,
                'practicalComments' => $attendance->practical_comments,
                'startsAt' => $attendance->trainingSession->starts_at?->toISOString(),
                'endsAt' => $attendance->trainingSession->ends_at?->toISOString(),
                'batch' => [
                    'name' => $attendance->trainingSession->trainingBatch->name,
                    'deliveryMode' => $attendance->trainingSession->trainingBatch->delivery_mode,
                    'instructor' => $attendance->trainingSession->trainingBatch->instructor?->name,
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function examsPayload(Enrollment $enrollment, bool $hasPaidAccess): array
    {
        return $enrollment->course->exams
            ->map(function ($exam) use ($enrollment, $hasPaidAccess): array {
                $attempts = $enrollment->examAttempts->where('exam_id', $exam->id);
                $lastAttempt = $attempts->sortByDesc('submitted_at')->first();

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'examType' => $exam->exam_type,
                    'durationMinutes' => $exam->duration_minutes,
                    'passMark' => $exam->pass_mark,
                    'maxAttempts' => $exam->max_attempts,
                    'attemptsUsed' => $attempts->count(),
                    'bestScore' => $attempts->max('score'),
                    'lastResult' => $lastAttempt?->result,
                    'canStart' => $hasPaidAccess && $attempts->count() < $exam->max_attempts,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function certificatesPayload(Enrollment $enrollment): array
    {
        return $enrollment->certificates
            ->sortByDesc('issued_at')
            ->map(fn ($certificate): array => [
                'id' => $certificate->id,
                'certificateNumber' => $certificate->certificate_number,
                'status' => $certificate->status,
                'result' => $certificate->result,
                'issuedAt' => $certificate->issued_at?->toISOString(),
                'expiresAt' => $certificate->expires_at?->toISOString(),
                'verifyUrl' => route('certificates.verify', ['token' => $certificate->verification_token]),
            ])
            ->values()
            ->all();
    }
}
