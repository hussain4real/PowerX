<?php

namespace App\Actions\PowerX;

use App\Models\Communication;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RescheduleTrainingSession
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private CreateCommunicationFromTemplate $createCommunicationFromTemplate,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(TrainingSession $trainingSession, User $actor, array $data): TrainingSession
    {
        return DB::transaction(function () use ($trainingSession, $actor, $data): TrainingSession {
            $lockedSession = TrainingSession::query()
                ->whereKey($trainingSession->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSession->loadMissing([
                'trainingBatch.team',
                'attendanceRecords.enrollment.studentProfile.company',
            ]);

            $startsAt = $this->dateTime($data['starts_at'] ?? null, 'starts_at');
            $endsAt = $this->dateTime($data['ends_at'] ?? null, 'ends_at');

            if ($endsAt->lessThanOrEqualTo($startsAt)) {
                throw ValidationException::withMessages([
                    'ends_at' => __('The session end time must be after the start time.'),
                ]);
            }

            $before = $this->sessionSnapshot($lockedSession);
            $reason = (string) ($data['reason'] ?? 'Schedule updated by training team.');
            $metadata = $lockedSession->metadata ?? [];
            $history = $metadata['reschedule_history'] ?? [];
            $history[] = [
                ...$before,
                'reason' => $reason,
                'actor_id' => $actor->id,
                'rescheduled_at' => now()->toISOString(),
            ];

            $lockedSession->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'venue' => $data['venue'] ?? $lockedSession->venue,
                'status' => $data['status'] ?? 'scheduled',
                'metadata' => [
                    ...$metadata,
                    'reschedule_reason' => $reason,
                    'reschedule_history' => $history,
                ],
            ]);

            $lockedSession->refresh()->loadMissing([
                'trainingBatch.team',
                'attendanceRecords.enrollment.studentProfile.company',
            ]);

            $this->recordAuditEvent->handle(
                action: 'training_session.rescheduled',
                subject: $lockedSession,
                actor: $actor,
                team: $lockedSession->trainingBatch->team,
                before: $before,
                after: $this->sessionSnapshot($lockedSession),
                metadata: [
                    'training_batch_id' => $lockedSession->training_batch_id,
                    'reason' => $reason,
                ],
                summary: __('Training session rescheduled.'),
            );

            $this->createScheduleChangeDrafts($lockedSession, $before, $reason);

            return $lockedSession;
        });
    }

    private function dateTime(mixed $value, string $field): CarbonInterface
    {
        if (blank($value)) {
            throw ValidationException::withMessages([
                $field => __('A session date and time is required.'),
            ]);
        }

        return $value instanceof CarbonInterface ? $value : Carbon::parse($value);
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionSnapshot(TrainingSession $trainingSession): array
    {
        return [
            'title' => $trainingSession->title,
            'status' => $trainingSession->status,
            'venue' => $trainingSession->venue,
            'starts_at' => $trainingSession->starts_at?->toISOString(),
            'ends_at' => $trainingSession->ends_at?->toISOString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     */
    private function createScheduleChangeDrafts(TrainingSession $trainingSession, array $before, string $reason): void
    {
        foreach ($trainingSession->attendanceRecords as $attendanceRecord) {
            $enrollment = $attendanceRecord->enrollment;
            $studentProfile = $enrollment->studentProfile;

            $recipientPhone = $studentProfile->mobile ?: $studentProfile->company?->phone;

            $this->createCommunicationFromTemplate->handle('class_schedule_changed', [
                'student_name' => $studentProfile->full_name,
                'session_title' => $trainingSession->title,
                'previous_session_time' => $before['starts_at'] ?? 'Not set',
                'new_session_time' => $trainingSession->starts_at?->format('d M Y H:i') ?? 'Not set',
                'venue' => $trainingSession->venue ?? 'PowerX training venue',
                'reason' => $reason,
                'recipient_phone' => $recipientPhone,
            ], [
                'team_id' => $trainingSession->trainingBatch->team_id,
                'student_profile_id' => $studentProfile->id,
                'company_id' => $studentProfile->company_id,
                'channel' => blank($recipientPhone) ? Communication::CHANNEL_EMAIL : Communication::CHANNEL_WHATSAPP,
                'metadata' => [
                    'training_session_id' => $trainingSession->id,
                    'attendance_record_id' => $attendanceRecord->id,
                    'change_type' => 'reschedule',
                ],
            ]);
        }
    }
}
