<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class TeacherController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $search = trim($request->input('search', ''));
        $periodId = session('selected_period_id')
            ?? AcademicPeriod::where('status', 'active')->value('id');

        $teachers = Teacher::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($periodId, function ($q) use ($periodId) {
                $q->withSum(
                    ['evaluationStatuses' => fn ($s) => $s->where('academic_period_id', $periodId)],
                    'expired_components'
                );
            })
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/teachers/Index', [
            'teachers' => $teachers,
            'filters' => ['search' => $search],
            'currentPeriod' => $periodId
                ? AcademicPeriod::find($periodId, ['id', 'name'])
                : null,
        ]);
    }

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
                    'id' => $teacher->id,
                    'dni' => $teacher->dni,
                    'full_name' => $teacher->full_name,
                    'email' => $teacher->email,
                    'is_active' => $teacher->is_active,
                    'expired_in_period' => (int) ($periodStats->expired ?? 0),
                    'records_in_period' => (int) ($periodStats->records ?? 0),
                ];
            });

        return response()->json([
            'period' => $period,
            'teachers' => $teachers,
        ]);
    }

    public function show(Teacher $teacher): \Inertia\Response
    {
        $teacher->load('user.roles');

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
            'total_records' => $baseQuery()->count(),
            'total_components' => $baseQuery()->sum('total_components'),
            'evaluated_components' => $baseQuery()->sum('evaluated_components'),
            'expired_components' => $baseQuery()->sum('expired_components'),
        ];

        return Inertia::render('admin/teachers/Show', [
            'teacher' => $teacher,
            'evaluationStatuses' => $evaluationStatuses,
            'stats' => $stats,
            'currentPeriod' => $period,
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'canManageAccess' => auth()->user()->hasRole('superadmin'),
        ]);
    }

    public function update(Request $request, Teacher $teacher): \Illuminate\Http\RedirectResponse
    {
        $teacher->load('user');

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => 'required|boolean',
        ]);

        $emailChanged = $teacher->user
            && isset($validated['email'])
            && $validated['email'] !== $teacher->user->email;

        \Illuminate\Support\Facades\DB::transaction(function () use ($teacher, $validated, $emailChanged) {
            $teacher->update($validated);

            if ($teacher->user) {
                $teacher->user->update([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'] ?? $teacher->user->email,
                ]);

                if ($emailChanged) {
                    \Illuminate\Support\Facades\DB::table('sessions')
                        ->where('user_id', $teacher->user->id)
                        ->delete();
                }
            }
        });

        return back()->with('success', 'Datos del docente actualizados correctamente.');
    }

    public function linkUser(Request $request, Teacher $teacher): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $teacher->load('user');

        // Si el docente ya tiene usuario vinculado, solo actualizar datos y rol
        if ($teacher->user) {
            $teacher->user->update([
                'name' => $teacher->full_name,
                'email' => $teacher->email ?? $teacher->user->email,
            ]);
            $teacher->user->syncRoles([$request->role]);

            return back()->with('success', 'Rol del usuario actualizado correctamente.');
        }

        abort_if(! $teacher->email, 422, 'El docente no tiene correo registrado.');

        // Buscar por email antes de crear para evitar duplicados
        $user = User::where('email', $teacher->email)->first();

        if ($user) {
            $user->update(['name' => $teacher->full_name]);
        } else {
            $user = User::create([
                'name' => $teacher->full_name,
                'email' => $teacher->email,
                'password' => Hash::make($teacher->dni),
            ]);
        }

        $user->syncRoles([$request->role]);
        $teacher->update(['user_id' => $user->id]);

        return back()->with('success', 'Acceso al sistema asignado correctamente.');
    }

    public function updateRole(Request $request, Teacher $teacher): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        if (! $teacher->user) {
            return back()->with('error', 'El docente no tiene un usuario vinculado.');
        }

        $teacher->user->syncRoles([$request->role]);

        return back()->with('success', 'Rol del usuario actualizado correctamente.');
    }

    public function unlinkUser(Teacher $teacher): \Illuminate\Http\RedirectResponse
    {
        $user = $teacher->user;

        if ($user) {
            \Illuminate\Support\Facades\DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();

            $user->syncRoles([]);
        }

        $teacher->update(['user_id' => null]);

        return back()->with('success', 'Acceso revocado y sesión cerrada correctamente.');
    }
}
