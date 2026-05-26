<?php

namespace App\Actions\PowerX;

use App\Models\AttendanceRecord;
use App\Models\Communication;
use App\Models\Enrollment;
use App\Models\TrainingBatch;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferEnrollmentToBatch
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private CreateCommunicationFromTemplate $createCommunicationFromTemplate,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, AttendanceRecord>
     */
    public function handle(Enrollment $enrollment, TrainingBatch $targetBatch, User $actor, array $data = []): Collection
    {
        return DB::transaction(function () use ($enrollment, $targetBatch, $actor, $data): Collection {
            $lockedEnrollment = Enrollment::query()
                ->whereKey($enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedTargetBatch = TrainingBatch::query()
                ->whereKey($targetBatch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedEnrollment->loadMissing(['studentProfile.company', 'course', 'team']);
            $lockedTargetBatch->loadMissing(['course', 'team', 'sessions' => fn ($query) => $query->orderBy('starts_at')]);

            $this->ensureTransferIsAllowed($lockedEnrollment, $lockedTargetBatch);

            $alreadyAssigned = $this->isAssignedToBatch($lockedEnrollment, $lockedTargetBatch);
            $assignedStudents = $this->assignedStudentCount($lockedTargetBatch);

            if (! $alreadyAssigned && $lockedTargetBatch->capacity > 0 && $assignedStudents >= $lockedTargetBatch->capacity) {
                throw ValidationException::withMessages([
                    'training_batch_id' => __('The target batch is already full.'),
                ]);
            }

            $before = [
                'metadata' => $lockedEnrollment->metadata,
                'source_batch_names' => $this->sourceBatchNames($lockedEnrollment, $lockedTargetBatch),
            ];
            $reason = (string) ($data['reason'] ?? 'Student transferred by admissions team.');
            $transferredAt = now();
            $targetAttendanceRecords = $lockedTargetBatch->sessions
                ->reject(fn ($session): bool => $session->status === 'cancelled')
                ->map(fn ($session): AttendanceRecord => $this->restoreOrCreateAttendance(
                    $session->id,
                    $lockedEnrollment,
                    $actor,
                    [
                        'status' => $data['status'] ?? 'pending',
                        'metadata' => [
                            'batch_transfer' => [
                                'target_batch_id' => $lockedTargetBatch->id,
                                'reason' => $reason,
                                'actor_id' => $actor->id,
                                'transferred_at' => $transferredAt->toISOString(),
                            ],
                        ],
                    ],
                ))
                ->values();

            $this->retireFutureSourceAttendance($lockedEnrollment, $lockedTargetBatch, $actor, $reason, $transferredAt);

            $metadata = $lockedEnrollment->metadata ?? [];
            $lockedEnrollment->update([
                'metadata' => [
                    ...$metadata,
                    'current_training_batch_id' => $lockedTargetBatch->id,
                    'batch_transfer' => [
                        'target_batch_id' => $lockedTargetBatch->id,
                        'target_batch_name' => $lockedTargetBatch->name,
                        'reason' => $reason,
                        'actor_id' => $actor->id,
                        'transferred_at' => $transferredAt->toISOString(),
                    ],
                ],
            ]);

            $lockedEnrollment->refresh();

            $this->recordAuditEvent->handle(
                action: 'enrollment.transferred',
                subject: $lockedEnrollment,
                actor: $actor,
                team: $lockedTargetBatch->team,
                before: $before,
                after: [
                    'metadata' => $lockedEnrollment->metadata,
                    'target_batch_id' => $lockedTargetBatch->id,
                    'attendance_record_ids' => $targetAttendanceRecords->pluck('id')->all(),
                ],
                metadata: [
                    'course_id' => $lockedEnrollment->course_id,
                    'target_batch_id' => $lockedTargetBatch->id,
                    'reason' => $reason,
                ],
                summary: __('Enrollment transferred to another training batch.'),
            );

            $this->createTransferDraft($lockedEnrollment, $lockedTargetBatch, $reason);

            return $targetAttendanceRecords;
        });
    }

    private function ensureTransferIsAllowed(Enrollment $enrollment, TrainingBatch $targetBatch): void
    {
        if ($enrollment->team_id !== $targetBatch->team_id) {
            throw ValidationException::withMessages([
                'training_batch_id' => __('The target batch belongs to a different PowerX team.'),
            ]);
        }

        if ($enrollment->course_id !== $targetBatch->course_id) {
            throw ValidationException::withMessages([
                'training_batch_id' => __('The target batch is for a different course.'),
            ]);
        }
    }

    private function isAssignedToBatch(Enrollment $enrollment, TrainingBatch $targetBatch): bool
    {
        return AttendanceRecord::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereHas('trainingSession', fn ($query) => $query->where('training_batch_id', $targetBatch->id))
            ->exists();
    }

    private function assignedStudentCount(TrainingBatch $targetBatch): int
    {
        return AttendanceRecord::query()
            ->whereHas('trainingSession', fn ($query) => $query->where('training_batch_id', $targetBatch->id))
            ->distinct('enrollment_id')
            ->count('enrollment_id');
    }

    /**
     * @return array<int, string>
     */
    private function sourceBatchNames(Enrollment $enrollment, TrainingBatch $targetBatch): array
    {
        return AttendanceRecord::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereHas('trainingSession.trainingBatch', fn ($query) => $query
                ->where('id', '!=', $targetBatch->id)
                ->where('team_id', $targetBatch->team_id)
                ->where('course_id', $targetBatch->course_id))
            ->with('trainingSession.trainingBatch:id,name')
            ->get()
            ->pluck('trainingSession.trainingBatch.name')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array{status: string, metadata: array<string, mixed>}  $data
     */
    private function restoreOrCreateAttendance(int $trainingSessionId, Enrollment $enrollment, User $actor, array $data): AttendanceRecord
    {
        $attendanceRecord = AttendanceRecord::withTrashed()->firstOrNew([
            'training_session_id' => $trainingSessionId,
            'enrollment_id' => $enrollment->id,
        ]);

        if ($attendanceRecord->trashed()) {
            $attendanceRecord->restore();
        }

        $attendanceRecord->fill([
            'team_id' => $enrollment->team_id,
            'marked_by_id' => $actor->id,
            'status' => $data['status'],
            'attended_at' => null,
            'metadata' => [
                ...($attendanceRecord->metadata ?? []),
                ...$data['metadata'],
            ],
        ]);
        $attendanceRecord->save();

        return $attendanceRecord;
    }

    private function retireFutureSourceAttendance(Enrollment $enrollment, TrainingBatch $targetBatch, User $actor, string $reason, CarbonInterface $transferredAt): void
    {
        AttendanceRecord::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereHas('trainingSession.trainingBatch', fn ($query) => $query
                ->where('id', '!=', $targetBatch->id)
                ->where('team_id', $targetBatch->team_id)
                ->where('course_id', $targetBatch->course_id))
            ->with('trainingSession.trainingBatch')
            ->get()
            ->filter(fn (AttendanceRecord $attendanceRecord): bool => $attendanceRecord->status === 'pending'
                || $attendanceRecord->trainingSession?->starts_at?->isFuture())
            ->each(function (AttendanceRecord $attendanceRecord) use ($targetBatch, $actor, $reason, $transferredAt): void {
                $attendanceRecord->update([
                    'status' => 'transferred',
                    'metadata' => [
                        ...($attendanceRecord->metadata ?? []),
                        'batch_transfer' => [
                            'target_batch_id' => $targetBatch->id,
                            'reason' => $reason,
                            'actor_id' => $actor->id,
                            'transferred_at' => $transferredAt->toISOString(),
                        ],
                    ],
                ]);
                $attendanceRecord->delete();
            });
    }

    private function createTransferDraft(Enrollment $enrollment, TrainingBatch $targetBatch, string $reason): void
    {
        $studentProfile = $enrollment->studentProfile;
        $recipientPhone = $studentProfile->mobile ?: $studentProfile->company?->phone;

        $this->createCommunicationFromTemplate->handle('batch_transfer', [
            'student_name' => $studentProfile->full_name,
            'course_title' => $enrollment->course?->title ?? $targetBatch->course?->title ?? 'PowerX course',
            'batch_name' => $targetBatch->name,
            'first_session_time' => $targetBatch->sessions->first()?->starts_at?->format('d M Y H:i') ?? 'To be confirmed',
            'venue' => $targetBatch->venue ?? 'PowerX training venue',
            'reason' => $reason,
            'recipient_phone' => $recipientPhone,
        ], [
            'team_id' => $targetBatch->team_id,
            'student_profile_id' => $studentProfile->id,
            'company_id' => $studentProfile->company_id,
            'channel' => blank($recipientPhone) ? Communication::CHANNEL_EMAIL : Communication::CHANNEL_WHATSAPP,
            'metadata' => [
                'enrollment_id' => $enrollment->id,
                'target_batch_id' => $targetBatch->id,
                'change_type' => 'batch_transfer',
            ],
        ]);
    }
}
