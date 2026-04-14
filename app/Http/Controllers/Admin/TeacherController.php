<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;

class TeacherController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $search = trim($request->input('search', ''));
        $noEmail = $request->boolean('no_email');
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
            ->when($noEmail, fn ($q) => $q->whereNull('email')->orWhere('email', ''))
            ->when($periodId, function ($q) use ($periodId) {
                $q->withSum(
                    ['evaluationStatuses' => fn ($s) => $s->where('academic_period_id', $periodId)],
                    'expired_components'
                );
            })
            ->orderBy('full_name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/teachers/Index', [
            'teachers' => $teachers,
            'filters' => ['search' => $search, 'no_email' => $noEmail],
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

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'dni' => ['required', 'string', 'max:20', 'unique:teachers,dni'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'dni.unique' => 'Ya existe un docente con ese DNI.',
        ]);

        Teacher::create([
            'dni' => trim($request->dni),
            'full_name' => trim($request->full_name),
            'email' => $request->email ? trim($request->email) : null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Docente creado correctamente.');
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

    public function updateEmail(Request $request, Teacher $teacher): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $teacher->load('user');

        $emailChanged = $teacher->user
            && isset($validated['email'])
            && $validated['email'] !== $teacher->user->email;

        \Illuminate\Support\Facades\DB::transaction(function () use ($teacher, $validated, $emailChanged) {
            $teacher->update(['email' => $validated['email']]);

            if ($teacher->user) {
                $teacher->user->update([
                    'email' => $validated['email'] ?? $teacher->user->email,
                ]);

                if ($emailChanged) {
                    \Illuminate\Support\Facades\DB::table('sessions')
                        ->where('user_id', $teacher->user->id)
                        ->delete();
                }
            }
        });

        return back()->with('success', 'Correo actualizado correctamente.');
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Docentes');

        $headers = ['DNI', 'Nombre Completo', 'Email'];
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col).'1';
            $sheet->setCellValue($cell, $header);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '087AB1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(36);

        $exampleStyle = ['font' => ['italic' => true, 'color' => ['rgb' => '888888']]];
        $sheet->setCellValue('A2', '12345678');
        $sheet->setCellValue('B2', 'Apellidos Nombres');
        $sheet->setCellValue('C2', 'correo@ejemplo.com');
        $sheet->getStyle('A2:C2')->applyFromArray($exampleStyle);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'plantilla_docentes.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.mimes' => 'El archivo debe ser .xlsx o .xls.',
            'file.max' => 'El archivo no debe superar 10 MB.',
        ]);

        $path = $request->file('file')->store('temp/teacher-imports');
        $fullPath = \Illuminate\Support\Facades\Storage::path($path);

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Storage::delete($path);

            return back()->withErrors(['file' => 'No se pudo leer el archivo Excel.']);
        }

        \Illuminate\Support\Facades\Storage::delete($path);

        $dataRows = array_slice($rows, 1);
        $created = 0;
        $emailUpdated = 0;
        $skipped = 0;
        $ignored = 0;

        foreach ($dataRows as $row) {
            $dni = isset($row[0]) ? trim((string) $row[0]) : null;

            if (! $dni) {
                $skipped++;

                continue;
            }

            $fullName = isset($row[1]) ? trim((string) $row[1]) : null;
            $email = isset($row[2]) ? trim((string) $row[2]) : null;
            $email = $email ?: null;

            $teacher = Teacher::withTrashed()->where('dni', $dni)->first();

            if ($teacher) {
                // Solo actualiza el email si el docente no tiene uno y el Excel trae uno
                if (! $teacher->email && $email) {
                    if ($teacher->trashed()) {
                        $teacher->restore();
                    }
                    $teacher->update(['email' => $email]);
                    $emailUpdated++;
                } else {
                    $ignored++;
                }

                continue;
            }

            if (! $fullName) {
                $skipped++;

                continue;
            }

            Teacher::create([
                'dni' => $dni,
                'full_name' => $fullName,
                'email' => $email,
                'is_active' => true,
            ]);
            $created++;
        }

        $parts = [];
        if ($created > 0) {
            $parts[] = "{$created} nuevo(s) registrado(s)";
        }
        if ($emailUpdated > 0) {
            $parts[] = "{$emailUpdated} correo(s) actualizado(s)";
        }
        if ($ignored > 0) {
            $parts[] = "{$ignored} omitido(s) (DNI ya existe con correo)";
        }
        if ($skipped > 0) {
            $parts[] = "{$skipped} omitido(s) (sin DNI o nombre)";
        }

        $message = 'Importación completada: '.(! empty($parts) ? implode(', ', $parts) : 'sin cambios').'.';

        return back()->with('success', $message);
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
