<?php

namespace App\Actions\PowerX;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RecordSessionAttendance
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(TrainingSession $trainingSession, Enrollment $enrollment, User $marker, array $data): AttendanceRecord
    {
        $trainingSession->loadMissing('trainingBatch');

        if ($trainingSession->trainingBatch->course_id !== $enrollment->course_id) {
            throw ValidationException::withMessages([
                'enrollment_id' => __('The enrollment does not belong to this training session course.'),
            ]);
        }

        return AttendanceRecord::updateOrCreate(
            [
                'training_session_id' => $trainingSession->id,
                'enrollment_id' => $enrollment->id,
            ],
            [
                'team_id' => $trainingSession->trainingBatch->team_id ?? $enrollment->team_id,
                'marked_by_id' => $marker->id,
                'status' => $data['status'] ?? 'present',
                'attended_at' => $data['attended_at'] ?? now(),
                'metadata' => [
                    'notes' => $data['notes'] ?? null,
                ],
            ],
        );
    }
}
