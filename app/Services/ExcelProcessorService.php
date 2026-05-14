<?php

namespace App\Services;

use App\Models\Course;
use App\Models\ExcelUpload;
use App\Models\Faculty;
use App\Models\ImportBatch;
use App\Models\NotificationBatch;
use App\Models\Program;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherEvaluationStatus;
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
                'status' => 'processing',
            ]);

            $filePath = Storage::path($upload->file_path);

            if (! Storage::exists($upload->file_path)) {
                throw new \Exception('Archivo no encontrado.');
            }

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $fileSize = Storage::size($upload->file_path);
            $dataRows = array_slice($rows, 1);
            $totalRows = count($dataRows);
            $failedRows = 0;

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
                'name' => 'Carga '.now()->format('Y-m-d H:i'),
                'academic_period_id' => $upload->academic_period_id,
                'campus_id' => $upload->campus_id,
                'imported_by' => Auth::id(),
                'excel_upload_id' => $upload->id,
                'file_name' => $upload->original_name ?? basename($upload->file_path),
                'file_size' => $fileSize,
                'total_rows' => $totalRows,
                'failed_rows' => 0,
                'imported_at' => now(),
                'is_active' => true,
            ]);

            /*
            🔁 Procesar filas del Excel
            */

            // Caché por nombre para evitar consultas repetidas por cada fila
            $facultyCache = [];
            $programCache = [];

            foreach ($dataRows as $row) {

                $dni = $row[1] ?? null;
                if (! $dni) {
                    $failedRows++;

                    continue;
                }

                $teacherName = $row[2] ?? 'Sin nombre';
                $facultyName = isset($row[3]) ? trim((string) $row[3]) : null;
                $programName = isset($row[4]) ? trim((string) $row[4]) : null;
                $cycle = isset($row[5]) ? substr($row[5], 0, 10) : null;
                $courseName = $row[6] ?? null;
                $group = isset($row[7]) ? substr($row[7], 0, 10) : null;
                $total = $row[8] ?? 0;
                $evaluated = $row[9] ?? 0;
                $expired = $row[10] ?? 0;

                /*
                🔹 Crear o buscar facultad
                */
                if ($facultyName && ! isset($facultyCache[$facultyName])) {
                    $faculty = Faculty::withTrashed()->where('name', $facultyName)->first();

                    if ($faculty) {
                        if ($faculty->trashed()) {
                            $faculty->restore();
                        }
                    } else {
                        $faculty = Faculty::create(['name' => $facultyName]);
                    }

                    $facultyCache[$facultyName] = $faculty->id;
                }

                /*
                🔹 Crear o buscar escuela profesional
                */
                if ($programName && ! isset($programCache[$programName])) {
                    $program = Program::withTrashed()->where('name', $programName)->first();

                    if ($program) {
                        if ($program->trashed()) {
                            $program->restore();
                        }
                    } else {
                        $program = Program::create(['name' => $programName]);
                    }

                    $programCache[$programName] = $program->id;
                }

                /*
                🔹 Crear o buscar docente (incluye soft-deleted para evitar
                   violación de unique en dni)
                */
                $teacher = Teacher::withTrashed()->where('dni', trim($dni))->first();

                if ($teacher) {
                    if ($teacher->trashed()) {
                        $teacher->restore();
                    }
                    $teacher->update(['full_name' => trim($teacherName), 'is_active' => true]);
                } else {
                    $teacher = Teacher::create([
                        'dni' => trim($dni),
                        'full_name' => trim($teacherName),
                        'is_active' => true,
                    ]);
                }

                /*
                🔹 Vincular docente a facultad/programa/campus/periodo
                */
                $facultyId = $facultyName ? ($facultyCache[$facultyName] ?? null) : null;
                $programId = $programName ? ($programCache[$programName] ?? null) : null;

                if ($facultyId && $programId) {
                    $assignment = TeacherAssignment::withTrashed()
                        ->where('teacher_id', $teacher->id)
                        ->where('campus_id', $upload->campus_id)
                        ->where('faculty_id', $facultyId)
                        ->where('program_id', $programId)
                        ->where('academic_period_id', $upload->academic_period_id)
                        ->first();

                    if ($assignment) {
                        if ($assignment->trashed()) {
                            $assignment->restore();
                        }
                    } else {
                        TeacherAssignment::create([
                            'teacher_id' => $teacher->id,
                            'campus_id' => $upload->campus_id,
                            'faculty_id' => $facultyId,
                            'program_id' => $programId,
                            'academic_period_id' => $upload->academic_period_id,
                        ]);
                    }
                }

                /*
                🔹 Crear o buscar curso
                */
                $course = null;

                if (! empty($courseName)) {

                    $cleanCourseName = trim($courseName);

                    $course = Course::firstOrCreate(
                        ['name' => $cleanCourseName],
                        [
                            'code' => 'CUR-'.substr(md5($cleanCourseName), 0, 6),
                            'credits' => 0,
                        ]
                    );
                }

                /*
                🔹 Insertar estado de evaluación
                */
                TeacherEvaluationStatus::create([
                    'import_batch_id' => $batch->id,
                    'excel_upload_id' => $upload->id,
                    'teacher_id' => $teacher->id,
                    'course_id' => $course ? $course->id : null,
                    'academic_period_id' => $upload->academic_period_id,
                    'campus_id' => $upload->campus_id,
                    'cycle' => $cycle,
                    'group' => $group,
                    'total_components' => (int) $total,
                    'evaluated_components' => (int) $evaluated,
                    'expired_components' => (int) $expired,
                ]);
            }

            /*
            ✅ Actualizar conteo de filas fallidas
            */
            $batch->update(['failed_rows' => $failedRows]);

            /*
            🔥 CREAR NotificationBatch AUTOMÁTICAMENTE
            */

            // Evitar duplicado (por seguridad extra)
            if (! NotificationBatch::where('import_batch_id', $batch->id)->exists()) {

                $notificationBatch = NotificationBatch::create([
                    'import_batch_id' => $batch->id,
                    'academic_period_id' => $batch->academic_period_id,
                    'campus_id' => $batch->campus_id,
                    'name' => 'Notificación Rubros Vencidos',
                    'execution_date' => now(),
                    'status' => 'draft',
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
                'status' => 'processed',
            ]);

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            $upload->update([
                'status' => 'failed',
            ]);

            throw new \Exception('Error al procesar Excel: '.$e->getMessage());
        }
    }
}
