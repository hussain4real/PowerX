<?php

namespace App\Actions\PowerX;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\PaymentTransaction;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BuildStudentPortal
{
    /**
     * @var array<int, string>
     */
    private const LESSON_MEDIA_COLLECTIONS = ['video', 'learning-materials'];

    private const LESSON_MEDIA_LINK_EXPIRY_MINUTES = 30;

    public function __construct(private ResolveStudentLessonAccess $resolveStudentLessonAccess) {}

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
                'enrollments.invoices.paymentTransactions',
                'enrollments.paymentTransactions.media',
            ])
            ->first();

        if (! $profile) {
            return $this->emptyPayload($team, $includeCourseCatalog);
        }

        $enrollments = $profile->enrollments;
        $portalEnrollments = $enrollments->map(fn (Enrollment $enrollment): array => $this->enrollmentPayload($enrollment, $team));
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
                'issuedCertificates' => $enrollments
                    ->flatMap(fn (Enrollment $enrollment) => $enrollment->certificates)
                    ->where('status', 'issued')
                    ->count(),
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
    private function enrollmentPayload(Enrollment $enrollment, Team $team): array
    {
        $accessStatus = $this->accessStatus($enrollment);
        $progress = $this->progressPayload($enrollment);

        return [
            'id' => $enrollment->id,
            'status' => $enrollment->status,
            'paymentStatus' => $enrollment->payment_status,
            'accessStatus' => $accessStatus,
            'hasPaidAccess' => $accessStatus === 'open',
            'accessStartsAt' => $this->dateTimeToIsoString($enrollment->access_starts_at),
            'accessExpiresAt' => $this->dateTimeToIsoString($enrollment->access_expires_at),
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
                    ->map(fn (Invoice $invoice): array => [
                        'id' => $invoice->id,
                        'number' => $invoice->number,
                        'type' => $invoice->type,
                        'status' => $invoice->status,
                        'currency' => $invoice->currency,
                        'total' => (float) $invoice->total,
                        'outstandingAmount' => $this->outstandingAmount($invoice),
                        'issuedAt' => $this->dateTimeToIsoString($invoice->issued_at),
                        'dueAt' => $this->dateTimeToIsoString($invoice->due_at),
                        'paidAt' => $this->dateTimeToIsoString($invoice->paid_at),
                        'offlineInstructions' => $this->offlinePaymentInstructions($invoice),
                        'offlinePaymentProofUrl' => route('student.payments.offline-proof.store', [
                            'current_team' => $team,
                            'invoice' => $invoice,
                        ]),
                        'invoicePdfUrl' => route('student.payments.invoices.pdf', [
                            'current_team' => $team,
                            'invoice' => $invoice,
                        ]),
                    ])
                    ->values()
                    ->all(),
                'payments' => $enrollment->paymentTransactions
                    ->sortByDesc('paid_at')
                    ->map(fn (PaymentTransaction $payment): array => [
                        'id' => $payment->id,
                        'method' => $payment->method,
                        'reference' => $payment->reference,
                        'status' => $payment->status,
                        'currency' => $payment->currency,
                        'amount' => (float) $payment->amount,
                        'paidAt' => $this->dateTimeToIsoString($payment->paid_at),
                        'reviewStatus' => data_get($payment->metadata, 'finance_review.status'),
                        'reviewNotes' => data_get($payment->metadata, 'finance_review.notes'),
                        'proofStatus' => $payment->hasMedia('payment-proofs') ? 'proof_uploaded' : 'proof_missing',
                        'receiptUrl' => $payment->status === PaymentTransaction::STATUS_APPROVED
                            ? route('student.payments.receipts.pdf', [
                                'current_team' => $team,
                                'paymentTransaction' => $payment,
                            ])
                            : null,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    private function accessStatus(Enrollment $enrollment): string
    {
        if ($enrollment->status === Enrollment::STATUS_REJECTED) {
            return 'admission_rejected';
        }

        if ($enrollment->status === Enrollment::STATUS_REQUEST_MORE_INFORMATION) {
            return 'information_requested';
        }

        if ($enrollment->status === Enrollment::STATUS_PENDING) {
            return 'admission_pending';
        }

        if ($enrollment->payment_status !== 'paid') {
            return 'payment_pending';
        }

        if (! in_array($enrollment->status, [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_COMPLETED], true) || $this->dateTimeIsFuture($enrollment->access_starts_at)) {
            return 'enrollment_pending';
        }

        if ($this->dateTimeIsPast($enrollment->access_expires_at)) {
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
            ->map(fn (CourseModule $module): array => [
                'id' => $module->id,
                'title' => $module->title,
                'summary' => $module->summary,
                'lessons' => $module->lessons
                    ->map(function (Lesson $lesson) use ($enrollment, $progressByLesson, $hasPaidAccess): array {
                        $progress = $progressByLesson->get($lesson->id);
                        $progressPercentage = $progress instanceof LessonProgress ? (int) $progress->progress_percentage : 0;
                        $lastPositionSeconds = $progress instanceof LessonProgress ? (int) $progress->last_position_seconds : 0;
                        $isCompleted = $progress instanceof LessonProgress && $progress->completed_at !== null;
                        $hasPreviewAccess = $this->resolveStudentLessonAccess->hasPreviewAccess($enrollment, $lesson);
                        $canViewLesson = $hasPaidAccess || $hasPreviewAccess;
                        $isLocked = ! $canViewLesson;

                        return [
                            'id' => $lesson->id,
                            'title' => $lesson->title,
                            'lessonType' => $lesson->lesson_type,
                            'durationMinutes' => $lesson->duration_minutes,
                            'isPreview' => $lesson->is_preview,
                            'isLocked' => $isLocked,
                            'canViewLesson' => $canViewLesson,
                            'canUpdateProgress' => $hasPaidAccess && ! $isLocked,
                            'viewerUrl' => $canViewLesson ? route('student.lessons.show', [
                                'current_team' => $enrollment->team,
                                'enrollment' => $enrollment,
                                'lesson' => $lesson,
                            ]) : null,
                            'content' => $isLocked ? null : $lesson->content,
                            'contentRevision' => $lesson->content_revision,
                            'progressPercentage' => $progressPercentage,
                            'lastPositionSeconds' => $lastPositionSeconds,
                            'isCompleted' => $isCompleted,
                            'media' => $this->lessonMediaPayload($enrollment, $lesson, $hasPaidAccess, $hasPreviewAccess),
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
    private function lessonMediaPayload(Enrollment $enrollment, Lesson $lesson, bool $hasPaidAccess, bool $hasPreviewAccess): array
    {
        if (! $hasPaidAccess && ! $hasPreviewAccess) {
            return [];
        }

        $expiresAt = now()->addMinutes(self::LESSON_MEDIA_LINK_EXPIRY_MINUTES);
        $source = $hasPaidAccess ? 'paid_lesson_media' : 'student_preview_media';

        return $lesson->media
            ->whereIn('collection_name', self::LESSON_MEDIA_COLLECTIONS)
            ->sortBy('order_column')
            ->map(function (Media $media) use ($enrollment, $lesson, $expiresAt, $source): array {
                $mediaType = $this->mediaType($media);
                $signedParameters = [
                    'current_team' => $enrollment->team,
                    'enrollment' => $enrollment,
                    'lesson' => $lesson,
                    'media' => $media,
                    'source' => $source,
                ];

                $inlineUrl = URL::temporarySignedRoute('student.lesson-media.show', $expiresAt, [
                    ...$signedParameters,
                    'disposition' => 'inline',
                ]);
                $downloadUrl = URL::temporarySignedRoute('student.lesson-media.show', $expiresAt, [
                    ...$signedParameters,
                    'disposition' => 'download',
                ]);

                return [
                    'id' => $media->id,
                    'name' => $media->name,
                    'fileName' => $media->file_name,
                    'collectionName' => $media->collection_name,
                    'collectionLabel' => $media->collection_name === 'video' ? 'Video' : 'Learning material',
                    'mimeType' => $media->mime_type,
                    'size' => $media->size,
                    'humanReadableSize' => $media->human_readable_size,
                    'url' => $downloadUrl,
                    'inlineUrl' => $inlineUrl,
                    'downloadUrl' => $downloadUrl,
                    'expiresAt' => $expiresAt->toISOString(),
                    'canPreviewInline' => in_array($mediaType, ['video', 'pdf'], true),
                    'mediaType' => $mediaType,
                ];
            })
            ->values()
            ->all();
    }

    private function mediaType(Media $media): string
    {
        if ($media->collection_name === 'video' || str_starts_with($media->mime_type, 'video/')) {
            return 'video';
        }

        if ($media->mime_type === 'application/pdf' || Str::of($media->file_name)->lower()->endsWith('.pdf')) {
            return 'pdf';
        }

        return 'download';
    }

    private function dateTimeToIsoString(mixed $value): ?string
    {
        return $value instanceof CarbonInterface ? $value->toISOString() : null;
    }

    private function outstandingAmount(Invoice $invoice): float
    {
        $approvedTotal = $invoice->paymentTransactions
            ->where('status', PaymentTransaction::STATUS_APPROVED)
            ->sum(fn (PaymentTransaction $payment): float => (float) $payment->amount);

        return max(0, (float) $invoice->total - (float) $approvedTotal);
    }

    private function offlinePaymentInstructions(Invoice $invoice): string
    {
        $methods = collect(PaymentTransaction::manualMethodOptions())->values()->join(', ', ' or ');
        $currency = config('powerx_payments.manual.bank_transfer.currency', $invoice->currency);

        return __('Submit :methods proof in :currency with invoice :invoice as the reference. Finance approval is required before paid access opens.', [
            'methods' => $methods,
            'currency' => $currency,
            'invoice' => $invoice->number,
        ]);
    }

    private function dateTimeIsFuture(mixed $value): bool
    {
        return $value instanceof CarbonInterface && $value->isFuture();
    }

    private function dateTimeIsPast(mixed $value): bool
    {
        return $value instanceof CarbonInterface && $value->isPast();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function schedulePayload(Enrollment $enrollment): array
    {
        return $enrollment->attendanceRecords
            ->sortBy(fn ($attendance): ?string => $this->dateTimeToIsoString($attendance->trainingSession?->starts_at))
            ->map(fn ($attendance): array => [
                'id' => $attendance->trainingSession->id,
                'title' => $attendance->trainingSession->title,
                'sessionType' => $attendance->trainingSession->session_type,
                'venue' => $attendance->trainingSession->venue,
                'status' => $attendance->status,
                'practicalOutcome' => $attendance->practical_outcome,
                'practicalComments' => $attendance->practical_comments,
                'startsAt' => $this->dateTimeToIsoString($attendance->trainingSession->starts_at),
                'endsAt' => $this->dateTimeToIsoString($attendance->trainingSession->ends_at),
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
                $activeAttempt = $attempts
                    ->whereNull('submitted_at')
                    ->sortByDesc('started_at')
                    ->first();
                $coursePackage = $this->coursePackage($enrollment);
                $maxAttempts = $coursePackage === null
                    ? $exam->max_attempts
                    : ($coursePackage->max_exam_attempts ?? $exam->max_attempts);

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'examType' => $exam->exam_type,
                    'durationMinutes' => $exam->duration_minutes,
                    'passMark' => $exam->pass_mark,
                    'maxAttempts' => $maxAttempts,
                    'attemptsUsed' => $attempts->count(),
                    'bestScore' => $attempts->max('score'),
                    'lastResult' => $lastAttempt?->result,
                    'lastAttemptUrl' => $lastAttempt ? route('student.exam-attempts.show', [
                        'current_team' => $enrollment->team,
                        'examAttempt' => $lastAttempt,
                    ]) : null,
                    'activeAttemptUrl' => $activeAttempt ? route('student.exam-attempts.show', [
                        'current_team' => $enrollment->team,
                        'examAttempt' => $activeAttempt,
                    ]) : null,
                    'startUrl' => route('student.exam-attempts.store', [
                        'current_team' => $enrollment->team,
                        'enrollment' => $enrollment,
                        'exam' => $exam,
                    ]),
                    'canStart' => $hasPaidAccess && $activeAttempt === null && $attempts->count() < $maxAttempts,
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
                'issuedAt' => $this->dateTimeToIsoString($certificate->issued_at),
                'expiresAt' => $this->dateTimeToIsoString($certificate->expires_at),
                'verifyUrl' => route('certificates.verify', ['token' => $certificate->verification_token]),
            ])
            ->values()
            ->all();
    }

    private function coursePackage(Enrollment $enrollment): ?CoursePackage
    {
        if ($enrollment->course_package_id === null) {
            return null;
        }

        return $enrollment->coursePackage;
    }
}
