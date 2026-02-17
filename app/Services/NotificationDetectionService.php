<?php

namespace App\Services;

use App\Models\ImportBatch;
use App\Models\TeacherEvaluationStatus;

class NotificationDetectionService
{
    public function getTeachersWithExpiredComponents($academicPeriodId, $campusId)
    {
        $activeBatch = ImportBatch::where('academic_period_id', $academicPeriodId)
            ->where('campus_id', $campusId)
            ->where('is_active', true)
            ->first();

        if (!$activeBatch) {
            return collect();
        }


        return TeacherEvaluationStatus::with(['teacher'])
            ->where('academic_period_id', $academicPeriodId)
            ->where('campus_id', $campusId)
            ->where('import_batch_id', $activeBatch->id)
            ->withExpired()
            ->get()
            ->groupBy('teacher_id');
    }
}
