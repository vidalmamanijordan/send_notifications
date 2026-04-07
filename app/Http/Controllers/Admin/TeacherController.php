<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeacherController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $periodId = session('selected_period_id')
            ?? AcademicPeriod::where('status', 'active')->value('id');

        $period = $periodId ? AcademicPeriod::find($periodId, ['id', 'name']) : null;

        $teachers = Teacher::where(function ($q) use ($query) {
            $q->where('dni', 'like', "%{$query}%")
              ->orWhere('full_name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%");
        })
            ->orderBy('full_name')
            ->limit(8)
            ->get(['id', 'dni', 'full_name', 'email', 'is_active'])
            ->map(function (Teacher $teacher) use ($periodId) {
                $periodStats = $periodId
                    ? $teacher->evaluationStatuses()
                        ->where('academic_period_id', $periodId)
                        ->selectRaw('SUM(expired_components) as expired, COUNT(*) as records')
                        ->first()
                    : null;

                return [
                    'id'               => $teacher->id,
                    'dni'              => $teacher->dni,
                    'full_name'        => $teacher->full_name,
                    'email'            => $teacher->email,
                    'is_active'        => $teacher->is_active,
                    'expired_in_period' => (int) ($periodStats->expired ?? 0),
                    'records_in_period' => (int) ($periodStats->records ?? 0),
                ];
            });

        return response()->json([
            'period'   => $period,
            'teachers' => $teachers,
        ]);
    }

    public function show(Teacher $teacher)
    {
        $periodId = session('selected_period_id')
            ?? AcademicPeriod::where('status', 'active')->value('id');

        $period = $periodId ? AcademicPeriod::find($periodId, ['id', 'name', 'status']) : null;

        $baseQuery = fn () => $teacher->evaluationStatuses()
            ->when($periodId, fn ($q) => $q->where('academic_period_id', $periodId));

        $evaluationStatuses = $baseQuery()
            ->with(['course', 'academicPeriod', 'campus'])
            ->orderByDesc('created_at')
            ->paginate(12);

        $stats = [
            'total_records'        => $baseQuery()->count(),
            'total_components'     => $baseQuery()->sum('total_components'),
            'evaluated_components' => $baseQuery()->sum('evaluated_components'),
            'expired_components'   => $baseQuery()->sum('expired_components'),
        ];

        return Inertia::render('admin/teachers/Show', [
            'teacher'            => $teacher,
            'evaluationStatuses' => $evaluationStatuses,
            'stats'              => $stats,
            'currentPeriod'      => $period,
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => ['nullable', 'email', 'max:255'],
            'is_active' => 'required|boolean',
        ]);

        $teacher->update($validated);

        return back()->with('success', 'Datos del docente actualizados correctamente.');
    }
}
