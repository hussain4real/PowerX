<?php

namespace App\Actions\PowerX;

use App\Models\Communication;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelTrainingSession
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private CreateCommunicationFromTemplate $createCommunicationFromTemplate,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(TrainingSession $trainingSession, User $actor, array $data = []): TrainingSession
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

            $before = $this->sessionSnapshot($lockedSession);
            $reason = (string) ($data['reason'] ?? 'Cancelled by training team.');
            $metadata = $lockedSession->metadata ?? [];
            $cancellations = $metadata['cancellation_history'] ?? [];
            $cancellations[] = [
                ...$before,
                'reason' => $reason,
                'actor_id' => $actor->id,
                'cancelled_at' => now()->toISOString(),
            ];

            $lockedSession->update([
                'status' => 'cancelled',
                'metadata' => [
                    ...$metadata,
                    'cancellation_reason' => $reason,
                    'cancellation_history' => $cancellations,
                ],
            ]);

            $lockedSession->refresh()->loadMissing([
                'trainingBatch.team',
                'attendanceRecords.enrollment.studentProfile.company',
            ]);

            $this->recordAuditEvent->handle(
                action: 'training_session.cancelled',
                subject: $lockedSession,
                actor: $actor,
                team: $lockedSession->trainingBatch->team,
                before: $before,
                after: $this->sessionSnapshot($lockedSession),
                metadata: [
                    'training_batch_id' => $lockedSession->training_batch_id,
                    'reason' => $reason,
                ],
                summary: __('Training session cancelled.'),
            );

            $this->createCancellationDrafts($lockedSession, $reason);

            return $lockedSession;
        });
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

    private function createCancellationDrafts(TrainingSession $trainingSession, string $reason): void
    {
        foreach ($trainingSession->attendanceRecords as $attendanceRecord) {
            $enrollment = $attendanceRecord->enrollment;
            $studentProfile = $enrollment->studentProfile;

            $recipientPhone = $studentProfile->mobile ?: $studentProfile->company?->phone;

            $this->createCommunicationFromTemplate->handle('class_cancelled', [
                'student_name' => $studentProfile->full_name,
                'session_title' => $trainingSession->title,
                'session_time' => $trainingSession->starts_at?->format('d M Y H:i') ?? 'Not set',
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
                    'change_type' => 'cancellation',
                ],
            ]);
        }
    }
}
