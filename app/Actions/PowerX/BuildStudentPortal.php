<?php

namespace App\Actions\PowerX;

use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BuildStudentPortal
{
    /**
     * @var array<int, string>
     */
    private const LESSON_MEDIA_COLLECTIONS = ['video', 'learning-materials'];

    private const LESSON_MEDIA_LINK_EXPIRY_MINUTES = 30;

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user, Team $team, bool $includeCourseCatalog = false): array
    {
        $profile = StudentProfile::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($team)
            ->with([
                'enrollments' => fn ($query) => $query->whereBelongsTo($team)->latest('id'),
                'enrollments.course.exams' => fn ($query) => $query->active()->orderBy('title'),
                'enrollments.course.modules' => fn ($query) => $query->active()->orderBy('sort_order'),
                'enrollments.course.modules.lessons' => fn ($query) => $query->active()->orderBy('sort_order'),
                'enrollments.course.modules.lessons.media',
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
            return $this->emptyPayload($team, $includeCourseCatalog);
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
            'courseCatalog' => $includeCourseCatalog ? $this->courseCatalogPayload($team) : [],
        ];
    }

    /**
     * @return array{profile: null, summary: array<string, int|string>, enrollments: array<int, mixed>, courseCatalog: array<int, mixed>}
     */
    private function emptyPayload(Team $team, bool $includeCourseCatalog): array
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
            'courseCatalog' => $includeCourseCatalog ? $this->courseCatalogPayload($team) : [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function courseCatalogPayload(Team $team): array
    {
        return Course::query()
            ->whereBelongsTo($team)
            ->published()
            ->with(['packages' => fn ($query) => $query->active()->orderBy('price')])
            ->withCount(['modules', 'enrollments'])
            ->latest('published_at')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Course $course): array => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'category' => $course->category,
                'summary' => $course->summary,
                'deliveryMode' => $course->delivery_mode,
                'currency' => $course->currency,
                'basePrice' => (float) $course->base_price,
                'validityDays' => $course->validity_days,
                'isFeatured' => $course->is_featured,
                'modulesCount' => $course->modules_count,
                'enrollmentsCount' => $course->enrollments_count,
                'lowestPackagePrice' => (float) ($course->packages->min('price') ?? $course->base_price),
                'url' => route('courses.show', ['course' => $course]),
                'packages' => $course->packages
                    ->map(fn (CoursePackage $package): array => [
                        'id' => $package->id,
                        'name' => $package->name,
                        'packageType' => $package->package_type,
                        'currency' => $package->currency,
                        'price' => (float) $package->price,
                        'discountPrice' => $package->discount_price ? (float) $package->discount_price : null,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
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

        if (! in_array($enrollment->status, ['active', 'completed'], true) || $enrollment->access_starts_at?->isFuture()) {
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
                    ->map(function ($lesson) use ($enrollment, $progressByLesson, $hasPaidAccess): array {
                        $progress = $progressByLesson->get($lesson->id);
                        $isLocked = ! $hasPaidAccess && ! $lesson->is_preview;

                        return [
                            'id' => $lesson->id,
                            'title' => $lesson->title,
                            'lessonType' => $lesson->lesson_type,
                            'durationMinutes' => $lesson->duration_minutes,
                            'isPreview' => $lesson->is_preview,
                            'isLocked' => $isLocked,
                            'canUpdateProgress' => $hasPaidAccess && ! $isLocked,
                            'content' => $isLocked ? null : $lesson->content,
                            'contentRevision' => $lesson->content_revision,
                            'progressPercentage' => (int) ($progress?->progress_percentage ?? 0),
                            'lastPositionSeconds' => (int) ($progress?->last_position_seconds ?? 0),
                            'isCompleted' => $progress?->completed_at !== null,
                            'media' => $this->lessonMediaPayload($enrollment, $lesson, $hasPaidAccess),
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
    private function lessonMediaPayload(Enrollment $enrollment, Lesson $lesson, bool $hasPaidAccess): array
    {
        if (! $hasPaidAccess) {
            return [];
        }

        $expiresAt = now()->addMinutes(self::LESSON_MEDIA_LINK_EXPIRY_MINUTES);

        return $lesson->media
            ->whereIn('collection_name', self::LESSON_MEDIA_COLLECTIONS)
            ->sortBy('order_column')
            ->map(fn (Media $media): array => [
                'id' => $media->id,
                'name' => $media->name,
                'fileName' => $media->file_name,
                'collectionName' => $media->collection_name,
                'collectionLabel' => $media->collection_name === 'video' ? 'Video' : 'Learning material',
                'mimeType' => $media->mime_type,
                'size' => $media->size,
                'humanReadableSize' => $media->human_readable_size,
                'url' => URL::temporarySignedRoute('student.lesson-media.show', $expiresAt, [
                    'current_team' => $enrollment->team,
                    'lesson' => $lesson,
                    'media' => $media,
                ]),
                'expiresAt' => $expiresAt->toISOString(),
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
