<?php

namespace App\Services;

use App\Models\ExcelUpload;
use App\Models\Teacher;
use App\Models\Course;
use App\Models\TeacherEvaluationStatus;
use App\Models\ImportBatch;
use App\Models\NotificationBatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelProcessorService
{
    public function process(ExcelUpload $upload)
    {
        DB::beginTransaction();

        try {

            /*
            🔄 Marcar como procesando
            */
            $upload->update([
                'status' => 'processing'
            ]);

            $filePath = Storage::path($upload->file_path);

            if (!Storage::exists($upload->file_path)) {
                throw new \Exception("Archivo no encontrado.");
            }

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            /*
            🔥 Desactivar lote activo anterior
            */
            ImportBatch::where('academic_period_id', $upload->academic_period_id)
                ->where('campus_id', $upload->campus_id)
                ->update(['is_active' => false]);

            /*
            🔥 Crear nuevo ImportBatch
            */
            $batch = ImportBatch::create([
                'name'               => 'Carga ' . now()->format('Y-m-d H:i'),
                'academic_period_id' => $upload->academic_period_id,
                'campus_id'          => $upload->campus_id,
                'imported_by'        => Auth::id(),
                'excel_upload_id'    => $upload->id,
                'file_name'          => basename($upload->file_path),
                'imported_at'        => now(),
                'is_active'          => true
            ]);

            /*
            🔁 Procesar filas del Excel
            */
            foreach (array_slice($rows, 1) as $row) {

                $dni = $row[2] ?? null;
                if (!$dni) continue;

                $teacherName = $row[1] ?? 'Sin nombre';
                $cycle       = isset($row[5]) ? substr($row[5], 0, 10) : null;
                $courseName  = $row[6] ?? null;
                $group       = isset($row[7]) ? substr($row[7], 0, 10) : null;
                $total       = $row[8] ?? 0;
                $evaluated   = $row[9] ?? 0;
                $expired     = $row[10] ?? 0;

                /*
                🔹 Crear o buscar docente
                */
                $teacher = Teacher::firstOrCreate(
                    ['dni' => trim($dni)],
                    [
                        'full_name' => trim($teacherName),
                        'is_active' => true,
                    ]
                );

                /*
                🔹 Crear o buscar curso
                */
                $course = null;

                if (!empty($courseName)) {

                    $cleanCourseName = trim($courseName);

                    $course = Course::firstOrCreate(
                        ['name' => $cleanCourseName],
                        [
                            'code'    => 'CUR-' . substr(md5($cleanCourseName), 0, 6),
                            'credits' => 0
                        ]
                    );
                }

                /*
                🔹 Insertar estado de evaluación
                */
                TeacherEvaluationStatus::create([
                    'import_batch_id'      => $batch->id,
                    'excel_upload_id'      => $upload->id,
                    'teacher_id'           => $teacher->id,
                    'course_id'            => $course ? $course->id : null,
                    'academic_period_id'   => $upload->academic_period_id,
                    'campus_id'            => $upload->campus_id,
                    'cycle'                => $cycle,
                    'group'                => $group,
                    'total_components'     => (int) $total,
                    'evaluated_components' => (int) $evaluated,
                    'expired_components'   => (int) $expired,
                ]);
            }

            /*
            🔥 CREAR NotificationBatch AUTOMÁTICAMENTE
            */

            // Evitar duplicado (por seguridad extra)
            if (!NotificationBatch::where('import_batch_id', $batch->id)->exists()) {

                $notificationBatch = NotificationBatch::create([
                    'import_batch_id'     => $batch->id,
                    'academic_period_id'  => $batch->academic_period_id,
                    'campus_id'           => $batch->campus_id,
                    'name'                => 'Notificación Rubros Vencidos',
                    'execution_date'      => now(),
                    'status'              => 'draft',
                ]);

                /*
                🔥 Agrupar docentes con rubros vencidos
                */
                $teachersWithPending = TeacherEvaluationStatus::where('import_batch_id', $batch->id)
                    ->where('expired_components', '>', 0)
                    ->selectRaw('teacher_id, COUNT(DISTINCT course_id) as pending_courses_count')
                    ->groupBy('teacher_id')
                    ->get();

                foreach ($teachersWithPending as $teacherData) {
                    $notificationBatch->details()->create([
                        'teacher_id' => $teacherData->teacher_id,
                        'pending_courses_count' => $teacherData->pending_courses_count,
                        'status' => 'pending',
                    ]);
                }
            }

            /*
            ✅ Marcar upload como procesado
            */
            $upload->update([
                'status' => 'processed'
            ]);

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            $upload->update([
                'status' => 'failed'
            ]);

            throw new \Exception("Error al procesar Excel: " . $e->getMessage());
        }
    }
}