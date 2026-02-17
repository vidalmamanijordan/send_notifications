<?php

namespace App\Services;

use App\Models\ExcelUpload;
use App\Models\Teacher;
use App\Models\Course;
use App\Models\TeacherEvaluationStatus;
use App\Models\ImportBatch;
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
            🔥 DESACTIVAR lote activo anterior
            */
            ImportBatch::where('academic_period_id', $upload->academic_period_id)
                ->where('campus_id', $upload->campus_id)
                ->update(['is_active' => false]);

            /*
            🔥 CREAR nuevo lote activo
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
            🔁 PROCESAR FILAS
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
                🔹 TEACHER
                */
                $teacher = Teacher::firstOrCreate(
                    ['dni' => trim($dni)],
                    [
                        'full_name' => trim($teacherName),
                        'is_active' => true,
                    ]
                );

                /*
                🔹 COURSE (CREA SI NO EXISTE)
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
                🔹 INSERT STATUS CON RELACIÓN CORRECTA
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
