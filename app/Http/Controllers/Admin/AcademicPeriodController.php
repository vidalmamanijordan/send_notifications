<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use Illuminate\Http\Request;

class AcademicPeriodController extends Controller
{
    public function index()
    {
        $academicPeriods = AcademicPeriod::orderBy('start_date', 'desc')->paginate(10);

        return inertia('admin/academic-periods/Index', [
            'academicPeriods' => $academicPeriods,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:academic_periods,code',
            'name' => 'required|string|max:150',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,closed',
        ]);

        if ($validated['status'] === 'active') {
            AcademicPeriod::where('status', 'active')
                ->update(['status' => 'closed']);
        }

        AcademicPeriod::create($validated);

        return redirect()
            ->route('admin.academic-periods.index')
            ->with('success', 'Periodo académico creado correctamente.');
    }

    public function update(Request $request, AcademicPeriod $academicPeriod)
    {
        if ($academicPeriod->status === 'closed') {
            return redirect()
                ->route('admin.academic-periods.index')
                ->with('warning', 'El periodo académico está cerrado y no puede modificarse.');
        }

        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:academic_periods,code,' . $academicPeriod->id,
            'name' => 'required|string|max:150',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,closed',
        ]);

        if ($validated['status'] === 'active') {
            AcademicPeriod::where('status', 'active')
                ->where('id', '!=', $academicPeriod->id)
                ->update(['status' => 'closed']);
        }

        $academicPeriod->update($validated);

        return redirect()
            ->route('admin.academic-periods.index')
            ->with('success', 'Periodo académico actualizado correctamente.');
    }



    public function destroy(AcademicPeriod $academicPeriod)
    {
        $academicPeriod->delete();

        return redirect()
            ->route('admin.academic-periods.index')
            ->with('success', 'Periodo académico eliminado correctamente.');
    }
}
