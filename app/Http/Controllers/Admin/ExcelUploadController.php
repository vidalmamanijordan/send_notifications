<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Campus;
use App\Models\ExcelUpload;
use App\Services\ExcelProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelUploadController extends Controller
{
    public function index(Request $request)
    {
        $periodId = session('selected_period_id')
            ?? AcademicPeriod::where('status', 'active')->value('id');
        $campusId = $request->input('campus_id');

        $uploadsQuery = ExcelUpload::with([
            'academicPeriod',
            'campus',
            'user',
            'importBatch.user',
        ]);

        if ($periodId) {
            $uploadsQuery->where('academic_period_id', $periodId);
        }

        if ($campusId) {
            $uploadsQuery->where('campus_id', $campusId);
        }

        $activePeriod = AcademicPeriod::select('id', 'name')
            ->where('status', 'active')
            ->first();

        return Inertia::render('admin/excel-uploads/Index', [
            'uploads' => $uploadsQuery->latest()->paginate(10)->withQueryString(),
            'activePeriod' => $activePeriod,
            'campus' => Campus::select('id', 'name')->orderBy('name')->get(),
            'filters' => ['campus_id' => $campusId],
            'templateUrl' => route('admin.excel-uploads.template'),
        ]);
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte');

        // Columnas en el orden exacto que espera ExcelProcessorService (índices 0-10)
        $headers = [
            'A' => 'N°',
            'B' => 'Docente',
            'C' => 'DNI',
            'D' => 'Facultad',
            'E' => 'E.P.',
            'F' => 'Ciclo',
            'G' => 'Curso',
            'H' => 'Grupo',
            'I' => 'N° de rubros a evaluar',
            'J' => 'N° de rubros evaluados',
            'K' => 'N° de rubros vencidos',
        ];

        foreach ($headers as $col => $header) {
            $sheet->setCellValue("{$col}1", $header);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '087AB1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $widths = ['A' => 6, 'B' => 36, 'C' => 12, 'D' => 24, 'E' => 24, 'F' => 10, 'G' => 36, 'H' => 10, 'I' => 20, 'J' => 14, 'K' => 14];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $exampleStyle = ['font' => ['italic' => true, 'color' => ['rgb' => '888888']]];
        $example = ['1', 'APELLIDOS NOMBRES', '12345678', 'Facultad de Ingeniería', 'Ing. de Sistemas', '2025-I', 'Cálculo I', 'A', '5', '3', '2'];

        foreach (array_keys($headers) as $i => $col) {
            $sheet->setCellValue("{$col}2", $example[$i]);
        }
        $sheet->getStyle('A2:K2')->applyFromArray($exampleStyle);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'plantilla_reporte_excel.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function store(Request $request, ExcelProcessorService $processor)
    {
        abort_if(! auth()->user()->can('excelUploads.create'), 403);
        $validated = $request->validate([
            'academic_period_id' => 'required|exists:academic_periods,id',
            'campus_id' => 'required|exists:campus,id',
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        DB::beginTransaction();

        try {

            // Guardar archivo
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $path = $file->store('excel_uploads');

            $upload = ExcelUpload::create([
                'academic_period_id' => $validated['academic_period_id'],
                'campus_id' => $validated['campus_id'],
                'uploaded_by' => Auth::id(),
                'file_path' => $path,
                'original_name' => $originalName,
                'status' => 'pending',
            ]);

            // 🔥 PROCESAR EXCEL AUTOMÁTICAMENTE
            $processor->process($upload);

            DB::commit();

            return redirect()
                ->route('admin.notification-batches.index')
                ->with('success', 'Excel subido y procesado correctamente. Aquí encontrarás el lote generado.');
        } catch (\Exception $e) {

            DB::rollBack();

            dd($e->getMessage());
        }
    }

    public function destroy(ExcelUpload $excelUpload)
    {
        abort_if(! auth()->user()->can('excelUploads.delete'), 403);
        if ($excelUpload->file_path && Storage::exists($excelUpload->file_path)) {
            Storage::delete($excelUpload->file_path);
        }

        $excelUpload->delete();

        return redirect()
            ->route('admin.excel-uploads.index')
            ->with('success', 'Carga eliminada correctamente');
    }
}
