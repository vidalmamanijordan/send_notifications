<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherEvaluationStatus;
use App\Models\ImportBatch;
use Inertia\Inertia;

class ExpiredEvaluationController extends Controller
{
    public function index()
    {
        // 🔹 Obtener el lote activo
        $activeBatch = ImportBatch::where('is_active', true)->latest()->first();

        $records = TeacherEvaluationStatus::with([
            'teacher',
            'course',
            'academicPeriod',
            'campus',
            'importBatch'
        ])
            ->where('expired_components', '>', 0)
            ->latest()
            ->paginate(10)
            ->through(function ($record) use ($activeBatch) {
                return [
                    'id' => $record->id,
                    'teacher' => $record->teacher?->full_name,
                    'course' => $record->course?->name,
                    'academic_period' => $record->academicPeriod?->name,
                    'campus' => $record->campus?->name,
                    'cycle' => $record->cycle,
                    'group' => $record->group,
                    'total_components' => $record->total_components,
                    'evaluated_components' => $record->evaluated_components,
                    'expired_components' => $record->expired_components,
                    'import_batch_id' => $record->import_batch_id,

                    // ⭐ Distinción clave
                    'is_active_batch' => $activeBatch
                        ? $record->import_batch_id === $activeBatch->id
                        : false,

                    'imported_at' => $record->created_at?->format('Y-m-d H:i')
                ];
            });

        return Inertia::render('admin/expired-evaluations/Index', [
            'records' => $records,
            'activeBatchId' => $activeBatch?->id
        ]);
    }
}
