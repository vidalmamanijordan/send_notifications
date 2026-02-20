<?php

namespace App\Services;

use App\Models\NotificationBatch;
use App\Models\NotificationBatchDetail;
use App\Models\TeacherEvaluationStatus;
use Illuminate\Support\Facades\DB;

class NotificationBatchBuilder
{
    public function build($academicPeriodId, $campusId)
    {
        DB::beginTransaction();

        try {

            $teachers = TeacherEvaluationStatus::query()
                ->where('academic_period_id', $academicPeriodId)
                ->where('campus_id', $campusId)
                ->where('expired_components', '>', 0)
                ->select('teacher_id')
                ->groupBy('teacher_id')
                ->get();

            if ($teachers->isEmpty()) {
                DB::rollBack();
                return null;
            }

            $batch = NotificationBatch::create([
                'academic_period_id' => $academicPeriodId,
                'campus_id' => $campusId,
                'name' => 'Notificación Rubros Vencidos',
                'execution_date' => now(),
                'status' => 'draft'
            ]);

            foreach ($teachers as $teacher) {

                $pendingCourses = TeacherEvaluationStatus::query()
                    ->where('teacher_id', $teacher->teacher_id)
                    ->where('academic_period_id', $academicPeriodId)
                    ->where('campus_id', $campusId)
                    ->where('expired_components', '>', 0)
                    ->count();

                NotificationBatchDetail::create([
                    'notification_batch_id' => $batch->id,
                    'teacher_id' => $teacher->teacher_id,
                    'pending_courses_count' => $pendingCourses
                ]);
            }

            DB::commit();

            return $batch;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
