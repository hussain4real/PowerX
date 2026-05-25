<?php

namespace App\Actions\PowerX;

use App\Models\AttendanceRecord;
use App\Models\User;

class RecordPracticalAssessment
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(AttendanceRecord $attendanceRecord, User $assessor, array $data): AttendanceRecord
    {
        $attendanceRecord->update([
            'assessed_by_id' => $assessor->id,
            'practical_outcome' => $data['practical_outcome'],
            'practical_score' => $data['practical_score'] ?? null,
            'practical_comments' => $data['practical_comments'] ?? null,
            'assessed_at' => $data['assessed_at'] ?? now(),
        ]);

        return $attendanceRecord->refresh();
    }
}
