<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Campus;
use App\Models\ExcelUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Services\ExcelProcessorService;
use Illuminate\Support\Facades\DB;

class ExcelUploadController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/excel-uploads/Index', [
            'uploads' => ExcelUpload::with([
                'academicPeriod',
                'campus',
                'user',
                'importBatch.user'
            ])
                ->latest()
                ->paginate(10),

            'academicPeriods' => AcademicPeriod::select('id', 'name')->get(),
            'campus' => Campus::select('id', 'name')->get(),
        ]);
    }


    public function store(Request $request, ExcelProcessorService $processor)
    {
        $validated = $request->validate([
            'academic_period_id' => 'required|exists:academic_periods,id',
            'campus_id'          => 'required|exists:campus,id',
            'file'               => 'required|file|mimes:xlsx,xls',
        ]);

        DB::beginTransaction();

        try {

            // Guardar archivo
            $path = $request->file('file')->store('excel_uploads');

            $upload = ExcelUpload::create([
                'academic_period_id' => $validated['academic_period_id'],
                'campus_id'          => $validated['campus_id'],
                'uploaded_by'        => Auth::id(),
                'file_path'          => $path,
                'status'             => 'pending',
            ]);

            // 🔥 PROCESAR EXCEL AUTOMÁTICAMENTE
            $processor->process($upload);

            DB::commit();

            return redirect()
                ->route('admin.excel-uploads.index')
                ->with('success', 'Excel subido y procesado correctamente');
        } catch (\Exception $e) {

            DB::rollBack();

            dd($e->getMessage());
        }
    }


    public function destroy(ExcelUpload $excelUpload)
    {
        if ($excelUpload->file_path && Storage::exists($excelUpload->file_path)) {
            Storage::delete($excelUpload->file_path);
        }

        $excelUpload->delete();

        return redirect()
            ->route('admin.excel-uploads.index')
            ->with('success', 'Carga eliminada correctamente');
    }
}
