<?php

namespace App\Actions\PowerX;

use App\Models\AttendanceRecord;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\ExamAttempt;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IssueCertificate
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    /**
     * Issue a certificate after validating PowerX completion requirements.
     */
    public function handle(Enrollment $enrollment, ?User $approver = null): Certificate
    {
        return DB::transaction(function () use ($enrollment, $approver) {
            $lockedEnrollment = Enrollment::query()
                ->whereKey($enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingCertificate = Certificate::query()
                ->where('enrollment_id', $lockedEnrollment->id)
                ->where('status', 'issued')
                ->first();

            if ($existingCertificate) {
                return $existingCertificate;
            }

            $lockedEnrollment->loadMissing(['course', 'coursePackage', 'studentProfile']);

            $this->ensureEligible($lockedEnrollment);

            $certificate = Certificate::create([
                'team_id' => $lockedEnrollment->team_id,
                'enrollment_id' => $lockedEnrollment->id,
                'student_profile_id' => $lockedEnrollment->student_profile_id,
                'course_id' => $lockedEnrollment->course_id,
                'approved_by_id' => $approver?->id,
                'certificate_number' => $this->certificateNumber(),
                'verification_token' => $this->verificationToken(),
                'status' => 'issued',
                'result' => 'passed',
                'issued_at' => now(),
                'expires_at' => now()->addYears(2),
                'metadata' => [
                    'eligibility' => $this->eligibilitySnapshot($lockedEnrollment),
                ],
            ]);

            $this->recordAuditEvent->handle(
                action: 'certificate.issued',
                subject: $certificate,
                actor: $approver,
                after: $this->certificateAuditSnapshot($certificate),
                metadata: [
                    'enrollment_id' => $lockedEnrollment->id,
                    'student_profile_id' => $lockedEnrollment->student_profile_id,
                    'course_id' => $lockedEnrollment->course_id,
                    'eligibility' => $certificate->metadata['eligibility'] ?? [],
                ],
                summary: __('Certificate issued.'),
            );

            return $certificate;
        });
    }

    private function ensureEligible(Enrollment $enrollment): void
    {
        if ($enrollment->status !== 'active' || $enrollment->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'enrollment_id' => __('The enrollment must be active and paid before issuing a certificate.'),
            ]);
        }

        if ($enrollment->coursePackage && ! $enrollment->coursePackage->includes_certificate) {
            throw ValidationException::withMessages([
                'course_package_id' => __('The selected package does not include a certificate.'),
            ]);
        }

        if ($enrollment->access_starts_at && $enrollment->access_starts_at->isFuture()) {
            throw ValidationException::withMessages([
                'enrollment_id' => __('Course access has not started yet.'),
            ]);
        }

        if ($enrollment->access_expires_at && $enrollment->access_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'enrollment_id' => __('Course access has expired.'),
            ]);
        }

        if ($this->activeLessonCount($enrollment) > $this->completedLessonCount($enrollment)) {
            throw ValidationException::withMessages([
                'lessons' => __('All active lessons must be completed before issuing a certificate.'),
            ]);
        }

        if (! $this->hasPassedExam($enrollment)) {
            throw ValidationException::withMessages([
                'exam_attempts' => __('At least one exam attempt must be passed before issuing a certificate.'),
            ]);
        }

        if ($this->hasFailedPracticalAssessment($enrollment)) {
            throw ValidationException::withMessages([
                'attendance_records' => __('A failed practical assessment prevents certificate issuance.'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function eligibilitySnapshot(Enrollment $enrollment): array
    {
        return [
            'active_lessons' => $this->activeLessonCount($enrollment),
            'completed_lessons' => $this->completedLessonCount($enrollment),
            'passed_exam' => $this->hasPassedExam($enrollment),
            'failed_practical' => $this->hasFailedPracticalAssessment($enrollment),
        ];
    }

    private function activeLessonCount(Enrollment $enrollment): int
    {
        return Lesson::query()
            ->active()
            ->whereHas('courseModule', fn ($query) => $query
                ->active()
                ->where('course_id', $enrollment->course_id))
            ->count();
    }

    private function completedLessonCount(Enrollment $enrollment): int
    {
        return LessonProgress::query()
            ->completed()
            ->where('enrollment_id', $enrollment->id)
            ->whereHas('lesson', fn ($query) => $query
                ->active()
                ->whereHas('courseModule', fn ($query) => $query
                    ->active()
                    ->where('course_id', $enrollment->course_id)))
            ->distinct('lesson_id')
            ->count('lesson_id');
    }

    private function hasPassedExam(Enrollment $enrollment): bool
    {
        return ExamAttempt::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('result', 'passed')
            ->whereHas('exam', fn ($query) => $query
                ->active()
                ->where('course_id', $enrollment->course_id))
            ->exists();
    }

    private function hasFailedPracticalAssessment(Enrollment $enrollment): bool
    {
        return AttendanceRecord::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('practical_outcome', 'failed')
            ->exists();
    }

    private function certificateNumber(): string
    {
        do {
            $number = 'PX-CERT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Certificate::where('certificate_number', $number)->exists());

        return $number;
    }

    private function verificationToken(): string
    {
        do {
            $token = Str::random(64);
        } while (Certificate::where('verification_token', $token)->exists());

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function certificateAuditSnapshot(Certificate $certificate): array
    {
        return [
            'status' => $certificate->status,
            'certificate_number' => $certificate->certificate_number,
            'verification_token' => $certificate->verification_token,
            'approved_by_id' => $certificate->approved_by_id,
            'issued_at' => $certificate->issued_at?->toISOString(),
            'expires_at' => $certificate->expires_at?->toISOString(),
        ];
    }
}
