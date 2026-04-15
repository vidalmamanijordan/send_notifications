<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\NotificationBatch;
use App\Models\NotificationBatchDetail;
use App\Models\PersonImport;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PersonImportController extends Controller
{
    public function index(): \Inertia\Response
    {
        $periodId = session('selected_period_id')
            ?: AcademicPeriod::where('status', 'active')->value('id');

        $imports = PersonImport::with('user')
            ->when($periodId, fn ($q) => $q->where('academic_period_id', $periodId))
            ->latest()
            ->paginate(10)
            ->through(fn ($import) => [
                'id' => $import->id,
                'file_name' => $import->file_name,
                'file_size' => $import->file_size,
                'total_rows' => $import->total_rows,
                'created_count' => $import->created_count,
                'email_updated_count' => $import->email_updated_count,
                'skipped_count' => $import->skipped_count,
                'ignored_count' => $import->ignored_count,
                'imported_by' => $import->user?->name ?? '—',
                'imported_at' => $import->created_at->toISOString(),
            ]);

        $groups = NotificationBatch::with(['details.teacher', 'createdBy'])
            ->where('type', NotificationBatch::TYPE_FREE)
            ->whereNotNull('created_by')
            ->latest()
            ->get()
            ->map(fn ($batch) => [
                'id' => $batch->id,
                'name' => $batch->name,
                'status' => $batch->status,
                'teachers_count' => $batch->details->count(),
                'teachers' => $batch->details->map(fn ($d) => [
                    'id' => $d->teacher->id,
                    'dni' => $d->teacher->dni,
                    'full_name' => $d->teacher->full_name,
                    'email' => $d->teacher->email,
                    'is_active' => (bool) $d->teacher->is_active,
                ]),
                'created_by' => $batch->createdBy?->name ?? '—',
                'created_at' => $batch->created_at->toISOString(),
            ]);

        return Inertia::render('admin/persons/Index', [
            'imports' => $imports,
            'groups' => $groups,
            'templateUrl' => route('admin.teachers.template'),
        ]);
    }

    private function resolveActivePeriod(): ?AcademicPeriod
    {
        $periodId = session('selected_period_id')
            ?: AcademicPeriod::where('status', 'active')->value('id');

        if (! $periodId) {
            return null;
        }

        $period = AcademicPeriod::find($periodId);

        return ($period && $period->status === 'active') ? $period : null;
    }

    public function import(Request $request): \Illuminate\Http\RedirectResponse
    {
        $period = $this->resolveActivePeriod();

        if (! $period) {
            return back()->with('warning', 'Solo puedes realizar importaciones durante un periodo académico activo.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.mimes' => 'El archivo debe ser .xlsx o .xls.',
            'file.max' => 'El archivo no debe superar 10 MB.',
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $fileSize = $uploadedFile->getSize();

        $path = $uploadedFile->store('temp/person-imports');
        $fullPath = Storage::path($path);

        try {
            $spreadsheet = IOFactory::load($fullPath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
        } catch (\Exception $e) {
            Storage::delete($path);

            return back()->withErrors(['file' => 'No se pudo leer el archivo Excel.']);
        }

        Storage::delete($path);

        $dataRows = array_slice($rows, 1);
        $created = 0;
        $emailUpdated = 0;
        $skipped = 0;
        $ignored = 0;
        $totalRows = count($dataRows);
        $processedTeacherIds = [];

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
                if (! $teacher->email && $email) {
                    if ($teacher->trashed()) {
                        $teacher->restore();
                    }
                    $teacher->update(['email' => $email]);
                    $emailUpdated++;
                } else {
                    $ignored++;
                }

                // Incluir en el lote independientemente del resultado
                $processedTeacherIds[] = $teacher->id;

                continue;
            }

            if (! $fullName) {
                $skipped++;

                continue;
            }

            $newTeacher = Teacher::create([
                'dni' => $dni,
                'full_name' => $fullName,
                'email' => $email,
                'is_active' => true,
            ]);
            $created++;
            $processedTeacherIds[] = $newTeacher->id;
        }

        PersonImport::create([
            'file_name' => $originalName,
            'file_size' => $fileSize,
            'total_rows' => $totalRows,
            'created_count' => $created,
            'email_updated_count' => $emailUpdated,
            'skipped_count' => $skipped,
            'ignored_count' => $ignored,
            'imported_by' => auth()->id(),
            'academic_period_id' => $period->id,
        ]);

        // Generar lote libre con los docentes procesados
        if (! empty($processedTeacherIds)) {
            $batchName = pathinfo($originalName, PATHINFO_FILENAME);

            $batch = NotificationBatch::create([
                'type' => NotificationBatch::TYPE_FREE,
                'name' => "Lote libre — {$batchName}",
                'status' => NotificationBatch::STATUS_DRAFT,
                'academic_period_id' => $period->id,
                'execution_date' => now(),
            ]);

            $now = now();
            $details = array_map(fn ($id) => [
                'notification_batch_id' => $batch->id,
                'teacher_id' => $id,
                'pending_courses_count' => 0,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ], $processedTeacherIds);

            NotificationBatchDetail::insert($details);
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

        $message = 'Importación completada: '.(! empty($parts) ? implode(', ', $parts) : 'sin cambios').'. El lote está listo para enviar.';

        return redirect()->route('admin.notification-batches.index')->with('success', $message);
    }

    public function quickStore(Request $request): \Illuminate\Http\JsonResponse
    {
        $period = $this->resolveActivePeriod();

        if (! $period) {
            return response()->json(['error' => 'No hay un periodo académico activo.'], 422);
        }

        $data = $request->validate([
            'dni' => 'required|string|max:20|unique:teachers,dni',
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.unique' => 'Ya existe un docente con ese DNI.',
            'full_name.required' => 'El nombre completo es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
        ]);

        $teacher = Teacher::create([
            'dni' => $data['dni'],
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'teacher' => [
                'id' => $teacher->id,
                'dni' => $teacher->dni,
                'full_name' => $teacher->full_name,
                'email' => $teacher->email,
            ],
        ]);
    }

    public function createGroup(Request $request): \Illuminate\Http\RedirectResponse
    {
        $period = $this->resolveActivePeriod();

        if (! $period) {
            return back()->with('warning', 'Solo puedes crear grupos durante un periodo académico activo.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'teacher_ids' => 'required|array|min:1',
            'teacher_ids.*' => 'integer|exists:teachers,id',
        ], [
            'name.required' => 'El nombre del grupo es obligatorio.',
            'teacher_ids.required' => 'Debes agregar al menos un docente.',
            'teacher_ids.min' => 'Debes agregar al menos un docente.',
        ]);

        $batch = NotificationBatch::create([
            'type' => NotificationBatch::TYPE_FREE,
            'name' => $data['name'],
            'status' => NotificationBatch::STATUS_DRAFT,
            'academic_period_id' => $period->id,
            'execution_date' => now(),
            'created_by' => auth()->id(),
        ]);

        $now = now();
        $details = array_map(fn ($id) => [
            'notification_batch_id' => $batch->id,
            'teacher_id' => $id,
            'pending_courses_count' => 0,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ], $data['teacher_ids']);

        NotificationBatchDetail::insert($details);

        return back()->with('success', "Grupo \"{$data['name']}\" creado con ".count($data['teacher_ids']).' docente(s).');
    }

    public function deleteGroup(NotificationBatch $notificationBatch): \Illuminate\Http\RedirectResponse
    {
        if ($notificationBatch->type !== NotificationBatch::TYPE_FREE) {
            return back()->with('warning', 'Solo puedes eliminar grupos libres.');
        }

        $name = $notificationBatch->name;
        $notificationBatch->details()->delete();
        $notificationBatch->delete();

        return back()->with('success', "Grupo \"{$name}\" eliminado correctamente.");
    }

    public function concretarGroup(NotificationBatch $notificationBatch): \Illuminate\Http\RedirectResponse
    {
        if ($notificationBatch->type !== NotificationBatch::TYPE_FREE || $notificationBatch->status !== NotificationBatch::STATUS_DRAFT) {
            return redirect()->route('admin.notification-batches.index')
                ->with('warning', 'Este grupo ya fue concretado o no puede modificarse.');
        }

        $notificationBatch->update(['status' => NotificationBatch::STATUS_ACTIVE]);

        return redirect()->route('admin.notification-batches.index')
            ->with('success', "Lote \"{$notificationBatch->name}\" concretado. Ya puedes enviar las notificaciones desde esta vista.");
    }

    public function updateGroup(Request $request, NotificationBatch $notificationBatch): \Illuminate\Http\RedirectResponse
    {
        if ($notificationBatch->type !== NotificationBatch::TYPE_FREE || $notificationBatch->status !== NotificationBatch::STATUS_DRAFT) {
            return back()->with('warning', 'Solo puedes editar grupos en estado borrador.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'teacher_ids' => 'required|array|min:1',
            'teacher_ids.*' => 'integer|exists:teachers,id',
        ], [
            'name.required' => 'El nombre del grupo es obligatorio.',
            'teacher_ids.required' => 'Debes agregar al menos un docente.',
            'teacher_ids.min' => 'Debes agregar al menos un docente.',
        ]);

        $notificationBatch->update(['name' => $data['name']]);

        $notificationBatch->details()->delete();

        $now = now();
        $details = array_map(fn ($id) => [
            'notification_batch_id' => $notificationBatch->id,
            'teacher_id' => $id,
            'pending_courses_count' => 0,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ], $data['teacher_ids']);

        NotificationBatchDetail::insert($details);

        return back()->with('success', "Grupo \"{$data['name']}\" actualizado correctamente.");
    }

    public function destroy(PersonImport $personImport): \Illuminate\Http\RedirectResponse
    {
        $personImport->delete();

        return back()->with('success', 'Registro eliminado correctamente.');
    }
}
