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

class ExcelUploadController extends Controller
{
    public function index()
    {
        $periodId = session('selected_period_id')
            ?? AcademicPeriod::where('status', 'active')->value('id');

        $uploadsQuery = ExcelUpload::with([
            'academicPeriod',
            'campus',
            'user',
            'importBatch.user',
        ]);

        if ($periodId) {
            $uploadsQuery->where('academic_period_id', $periodId);
        }

        $activePeriod = AcademicPeriod::select('id', 'name')
            ->where('status', 'active')
            ->first();

        return Inertia::render('admin/excel-uploads/Index', [
            'uploads' => $uploadsQuery->latest()->paginate(10),
            'activePeriod' => $activePeriod,
            'campus' => Campus::select('id', 'name')->get(),
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
                ->route('admin.excel-uploads.index')
                ->with('success', 'Excel subido y procesado correctamente');
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
