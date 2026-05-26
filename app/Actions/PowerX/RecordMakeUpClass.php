<?php

namespace App\Actions\PowerX;

use App\Models\AttendanceRecord;
use App\Models\Communication;
use App\Models\Enrollment;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordMakeUpClass
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private CreateCommunicationFromTemplate $createCommunicationFromTemplate,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(TrainingSession $makeUpSession, Enrollment $enrollment, User $actor, array $data = []): AttendanceRecord
    {
        return DB::transaction(function () use ($makeUpSession, $enrollment, $actor, $data): AttendanceRecord {
            $lockedSession = TrainingSession::query()
                ->whereKey($makeUpSession->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedEnrollment = Enrollment::query()
                ->whereKey($enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSession->loadMissing(['trainingBatch.team', 'trainingBatch.course']);
            $lockedEnrollment->loadMissing(['studentProfile.company', 'course']);

            $this->ensureMakeUpIsAllowed($lockedSession, $lockedEnrollment);

            $missedAttendanceRecord = $this->missedAttendanceRecord($lockedEnrollment, $data['missed_attendance_record_id'] ?? null);
            $reason = (string) ($data['reason'] ?? 'Make-up class assigned by training team.');
            $status = (string) ($data['status'] ?? 'pending');
            $attendanceRecord = AttendanceRecord::withTrashed()->firstOrNew([
                'training_session_id' => $lockedSession->id,
                'enrollment_id' => $lockedEnrollment->id,
            ]);

            if ($attendanceRecord->trashed()) {
                $attendanceRecord->restore();
            }

            $before = $attendanceRecord->exists ? $this->attendanceSnapshot($attendanceRecord) : null;

            $attendanceRecord->fill([
                'team_id' => $lockedSession->trainingBatch->team_id,
                'marked_by_id' => $actor->id,
                'status' => $status,
                'attended_at' => $data['attended_at'] ?? ($status === 'pending' ? null : now()),
                'metadata' => [
                    ...($attendanceRecord->metadata ?? []),
                    'make_up_class' => [
                        'missed_attendance_record_id' => $missedAttendanceRecord?->id,
                        'reason' => $reason,
                        'notes' => $data['notes'] ?? null,
                        'actor_id' => $actor->id,
                        'recorded_at' => now()->toISOString(),
                    ],
                ],
            ]);
            $attendanceRecord->save();

            if ($missedAttendanceRecord) {
                $missedAttendanceRecord->update([
                    'metadata' => [
                        ...($missedAttendanceRecord->metadata ?? []),
                        'make_up_attendance_record_id' => $attendanceRecord->id,
                    ],
                ]);
            }

            $attendanceRecord->refresh()->loadMissing([
                'trainingSession.trainingBatch',
                'enrollment.studentProfile.company',
            ]);

            $this->recordAuditEvent->handle(
                action: 'attendance.make_up_recorded',
                subject: $attendanceRecord,
                actor: $actor,
                team: $lockedSession->trainingBatch->team,
                before: $before,
                after: $this->attendanceSnapshot($attendanceRecord),
                metadata: [
                    'enrollment_id' => $lockedEnrollment->id,
                    'make_up_session_id' => $lockedSession->id,
                    'missed_attendance_record_id' => $missedAttendanceRecord?->id,
                    'reason' => $reason,
                ],
                summary: __('Make-up class recorded.'),
            );

            $this->createMakeUpDraft($attendanceRecord, $reason);

            return $attendanceRecord;
        });
    }

    private function ensureMakeUpIsAllowed(TrainingSession $makeUpSession, Enrollment $enrollment): void
    {
        $batch = $makeUpSession->trainingBatch;

        if ($enrollment->team_id !== $batch->team_id) {
            throw ValidationException::withMessages([
                'training_session_id' => __('The make-up session belongs to a different PowerX team.'),
            ]);
        }

        if ($enrollment->course_id !== $batch->course_id) {
            throw ValidationException::withMessages([
                'training_session_id' => __('The make-up session is for a different course.'),
            ]);
        }

        $alreadyAssigned = AttendanceRecord::query()
            ->where('training_session_id', $makeUpSession->id)
            ->where('enrollment_id', $enrollment->id)
            ->exists();

        $assignedStudents = AttendanceRecord::query()
            ->where('training_session_id', $makeUpSession->id)
            ->distinct('enrollment_id')
            ->count('enrollment_id');

        if (! $alreadyAssigned && $batch->capacity > 0 && $assignedStudents >= $batch->capacity) {
            throw ValidationException::withMessages([
                'training_session_id' => __('The make-up session batch is already full.'),
            ]);
        }
    }

    private function missedAttendanceRecord(Enrollment $enrollment, mixed $attendanceRecordId): ?AttendanceRecord
    {
        if (blank($attendanceRecordId)) {
            return null;
        }

        $attendanceRecord = AttendanceRecord::query()
            ->whereKey($attendanceRecordId)
            ->with('trainingSession.trainingBatch')
            ->firstOrFail();

        if ($attendanceRecord->enrollment_id !== $enrollment->id) {
            throw ValidationException::withMessages([
                'missed_attendance_record_id' => __('The missed attendance record belongs to a different enrollment.'),
            ]);
        }

        if ($attendanceRecord->trainingSession?->trainingBatch?->course_id !== $enrollment->course_id) {
            throw ValidationException::withMessages([
                'missed_attendance_record_id' => __('The missed attendance record belongs to a different course.'),
            ]);
        }

        return $attendanceRecord;
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceSnapshot(AttendanceRecord $attendanceRecord): array
    {
        return [
            'training_session_id' => $attendanceRecord->training_session_id,
            'enrollment_id' => $attendanceRecord->enrollment_id,
            'status' => $attendanceRecord->status,
            'attended_at' => $attendanceRecord->attended_at?->toISOString(),
            'metadata' => $attendanceRecord->metadata,
        ];
    }

    private function createMakeUpDraft(AttendanceRecord $attendanceRecord, string $reason): void
    {
        $studentProfile = $attendanceRecord->enrollment->studentProfile;
        $trainingSession = $attendanceRecord->trainingSession;

        $recipientPhone = $studentProfile->mobile ?: $studentProfile->company?->phone;

        $this->createCommunicationFromTemplate->handle('make_up_class', [
            'student_name' => $studentProfile->full_name,
            'session_title' => $trainingSession->title,
            'session_time' => $trainingSession->starts_at?->format('d M Y H:i') ?? 'To be confirmed',
            'venue' => $trainingSession->venue ?? $trainingSession->trainingBatch?->venue ?? 'PowerX training venue',
            'reason' => $reason,
            'recipient_phone' => $recipientPhone,
        ], [
            'team_id' => $attendanceRecord->team_id,
            'student_profile_id' => $studentProfile->id,
            'company_id' => $studentProfile->company_id,
            'channel' => blank($recipientPhone) ? Communication::CHANNEL_EMAIL : Communication::CHANNEL_WHATSAPP,
            'metadata' => [
                'attendance_record_id' => $attendanceRecord->id,
                'training_session_id' => $trainingSession->id,
                'change_type' => 'make_up_class',
            ],
        ]);
    }
}
