<?php

namespace App\Services;

use App\Models\ExcelUpload;
use App\Models\Teacher;
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

            // 🔥 Crear lote
            // 1. Desactivar lote activo anterior
            ImportBatch::where('academic_period_id', $upload->academic_period_id)
                ->where('campus_id', $upload->campus_id)
                ->update(['is_active' => false]);


            // 2. Crear nuevo lote activo
            $batch = ImportBatch::create([
                'name'               => 'Carga ' . now()->format('Y-m-d H:i'),
                'academic_period_id' => $upload->academic_period_id,
                'campus_id'          => $upload->campus_id,
                'imported_by'        => Auth::id(),
                'excel_upload_id' => $upload->id,
                'file_name'          => basename($upload->file_path),
                'imported_at'        => now(),
                'is_active'          => true
            ]);




            foreach (array_slice($rows, 1) as $row) {

                $dni = $row[2] ?? null;

                if (!$dni) continue;

                $teacher = Teacher::firstOrCreate(
                    ['dni' => $dni],
                    [
                        'full_name' => $row[1] ?? 'Sin nombre',
                        'is_active' => true,
                    ]
                );

                TeacherEvaluationStatus::create([
                    'import_batch_id'      => $batch->id,   // 🔥 SOLUCIÓN
                    'excel_upload_id'      => $upload->id,
                    'teacher_id'           => $teacher->id,
                    'academic_period_id'   => $upload->academic_period_id,
                    'campus_id'            => $upload->campus_id,
                    'cycle'                => $row[5] ?? null,
                    'group'                => $row[7] ?? null,
                    'total_components'     => $row[8] ?? 0,
                    'evaluated_components' => $row[9] ?? 0,
                    'expired_components'   => $row[10] ?? 0,
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
