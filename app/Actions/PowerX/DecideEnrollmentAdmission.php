<?php

namespace App\Actions\PowerX;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecideEnrollmentAdmission
{
    public const DECISION_APPROVE = 'approve';

    public const DECISION_REJECT = 'reject';

    public const DECISION_REQUEST_INFORMATION = 'request_information';

    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private CreateCommunicationFromTemplate $createCommunicationFromTemplate,
    ) {}

    public function handle(Enrollment $enrollment, User $actor, string $decision, ?string $notes = null): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $actor, $decision, $notes): Enrollment {
            $enrollment = Enrollment::query()
                ->with(['course', 'studentProfile'])
                ->whereKey($enrollment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $before = $this->enrollmentAuditSnapshot($enrollment);
            $metadata = $enrollment->metadata ?? [];
            $metadata['admission'] = [
                'decision' => $decision,
                'decided_by_id' => $actor->id,
                'decided_at' => now()->toISOString(),
                'notes' => $notes,
            ];

            $updates = match ($decision) {
                self::DECISION_APPROVE => [
                    'status' => $enrollment->payment_status === 'paid'
                        ? Enrollment::STATUS_ACTIVE
                        : Enrollment::STATUS_APPROVED,
                    'approved_by_id' => $actor->id,
                    'approved_at' => $enrollment->approved_at ?? now(),
                    'metadata' => $metadata,
                ],
                self::DECISION_REJECT => [
                    'status' => Enrollment::STATUS_REJECTED,
                    'approved_by_id' => null,
                    'approved_at' => null,
                    'access_starts_at' => null,
                    'access_expires_at' => null,
                    'metadata' => $metadata,
                ],
                self::DECISION_REQUEST_INFORMATION => [
                    'status' => Enrollment::STATUS_REQUEST_MORE_INFORMATION,
                    'metadata' => $metadata,
                ],
                default => throw ValidationException::withMessages([
                    'decision' => __('Unsupported admission decision.'),
                ]),
            };

            $enrollment->update($updates);
            $enrollment->refresh();

            $this->recordAuditEvent->handle(
                action: match ($decision) {
                    self::DECISION_APPROVE => 'enrollment.approved',
                    self::DECISION_REJECT => 'enrollment.rejected',
                    self::DECISION_REQUEST_INFORMATION => 'enrollment.information_requested',
                    default => 'enrollment.decision_recorded',
                },
                subject: $enrollment,
                actor: $actor,
                before: $before,
                after: $this->enrollmentAuditSnapshot($enrollment),
                metadata: [
                    'decision' => $decision,
                    'student_profile_id' => $enrollment->student_profile_id,
                    'course_id' => $enrollment->course_id,
                ],
                summary: match ($decision) {
                    self::DECISION_APPROVE => __('Enrollment approved.'),
                    self::DECISION_REJECT => __('Enrollment rejected.'),
                    self::DECISION_REQUEST_INFORMATION => __('More enrollment information requested.'),
                    default => __('Enrollment decision recorded.'),
                },
            );

            $this->createDecisionDraft($enrollment, $decision, $notes);

            return $enrollment;
        });
    }

    private function createDecisionDraft(Enrollment $enrollment, string $decision, ?string $notes): void
    {
        $templateKey = match ($decision) {
            self::DECISION_APPROVE => 'enrollment_approved',
            self::DECISION_REJECT => 'enrollment_rejected',
            self::DECISION_REQUEST_INFORMATION => 'enrollment_request_information',
        };

        $student = $enrollment->studentProfile;
        $course = $enrollment->course;

        $this->createCommunicationFromTemplate->handle($templateKey, [
            'student_name' => $student->full_name,
            'course_title' => $course->title,
            'admission_note' => filled($notes) ? $notes : __('Please contact PowerX admissions for details.'),
            'recipient_phone' => $student->mobile,
        ], [
            'team_id' => $enrollment->team_id,
            'student_profile_id' => $student->id,
            'company_id' => $enrollment->company_id,
            'status' => 'draft',
            'metadata' => [
                'enrollment_id' => $enrollment->id,
                'decision' => $decision,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrollmentAuditSnapshot(Enrollment $enrollment): array
    {
        return [
            'status' => $enrollment->status,
            'approved_by_id' => $enrollment->approved_by_id,
            'approved_at' => $enrollment->approved_at?->toISOString(),
            'access_starts_at' => $enrollment->access_starts_at?->toISOString(),
            'access_expires_at' => $enrollment->access_expires_at?->toISOString(),
            'metadata' => $enrollment->metadata,
        ];
    }
}
