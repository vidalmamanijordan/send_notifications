<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationBatchJob;
use App\Models\AcademicPeriod;
use App\Models\Campus;
use App\Models\NotificationBatch;
use App\Models\NotificationBatchDetail;
use App\Models\NotificationTemplate;
use App\Models\Office;
use App\Models\TeacherEvaluationStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class NotificationBatchController extends Controller
{
    /**
     * Vista principal (SPA)
     */
    public function index(Request $request)
    {
        // Periodo: filtro explícito de la URL tiene prioridad, luego sesión global
        $periodId = $request->get('academic_period_id')
            ?: (session('selected_period_id')
                ?: AcademicPeriod::where('status', 'active')->value('id'));

        $query = NotificationBatch::with([
            'academicPeriod',
            'campus',
            'notificationTemplate',
            'office',
        ]);

        if ($periodId) {
            $query->where('academic_period_id', $periodId);
        }

        if ($request->campus_id) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return Inertia::render('admin/notifications/Index', [
            'batches' => $query->latest()->paginate(10)->withQueryString(),
            'academicPeriods' => AcademicPeriod::select('id', 'name')->get(),
            'campus' => Campus::select('id', 'name')->get(),
            'templates' => NotificationTemplate::select('id', 'name')->get(),
            'offices' => Office::where('is_active', 1)
                ->orderBy('level')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'cc_email', 'level', 'signature']),
            'filters' => [
                'academic_period_id' => $periodId ?? '',
                'campus_id' => $request->campus_id ?? '',
                'status' => $request->status ?? '',
            ],
        ]);
    }

    public function getTemplates()
    {
        return NotificationTemplate::select('id', 'name', 'subject', 'body', 'is_active')->latest()->get();
    }

    public function attachTemplate(Request $request, NotificationBatch $notificationBatch)
    {
        $request->validate([
            'notification_template_id' => 'required|exists:notification_templates,id',
        ]);

        // SOLO permitir si está en draft o active
        if (! in_array($notificationBatch->status, ['draft', 'active'])) {
            return

                back()->with(
                    'error',
                    'No se puede cambiar la plantilla porque el lote ya fue procesado.'
                );
        }

        $template = NotificationTemplate::findOrFail($request->notification_template_id);

        $notificationBatch->update([
            'notification_template_id' => $template->id,
            'subject' => $template->subject,
            'body' => $template->body,
            'status' => NotificationBatch::STATUS_ACTIVE,
        ]);

        return back()->with('success', 'Plantilla asociada correctamente');
    }

    public function assignOffice(Request $request, NotificationBatch $notificationBatch)
    {
        $data = $request->validate([
            'office_id' => [
                'required',
                Rule::exists('offices', 'id')->where('is_active', 1),
            ],
        ]);

        // Evitar cambio si el lote ya fue finalizado
        if (in_array($notificationBatch->status, [
            NotificationBatch::STATUS_PROCESSING,
            NotificationBatch::STATUS_COMPLETED,
            NotificationBatch::STATUS_COMPLETED_WITH_ERRORS,
        ])) {
            return back()->with([
                'error' => 'No se puede cambiar la oficina porque el lote ya está en proceso o finalizado.',
            ]);
        }

        $notificationBatch->update([
            'office_id' => $data['office_id'],
        ]);

        return back()->with([
            'success' => 'Oficina asignada correctamente.',
        ]);
    }

    public function preview(NotificationBatch $notificationBatch)
    {
        $notificationBatch->load('office');

        // Traer solo algunos docentes para preview (evita consultas pesadas)
        $batchDetails = $notificationBatch->details()
            ->with('teacher')
            ->limit(10)
            ->get();

        $teachers = $batchDetails->map(function ($detail) {
            if (! $detail->teacher) {
                return null;
            }

            return [
                'id' => $detail->teacher->id,
                'name' => $detail->teacher->full_name ?? 'Docente',
                'email' => $detail->teacher->email,
            ];
        })
            ->filter()
            ->values();

        $emails = $teachers
            ->pluck('email')
            ->filter()
            ->values();

        $firstTeacher = $teachers->first();
        $body = $notificationBatch->body ?? '';

        if ($body && $firstTeacher) {
            $courses = TeacherEvaluationStatus::where('teacher_id', $firstTeacher['id'])
                ->where('import_batch_id', $notificationBatch->import_batch_id)
                ->where('expired_components', '>', 0)
                ->with('course')
                ->get()
                ->map(fn ($c) => "- {$c->course?->name} (Ciclo: {$c->cycle}, Grupo: {$c->group})")
                ->implode("\n");

            $body = str_replace(
                ['{docente}', '{cursos}'],
                [$firstTeacher['name'], $courses ?: '- (sin cursos)'],
                $body
            );
        }

        // Renderizar cuerpo igual que el Job
        $htmlBody = $this->renderBody($body);

        // Firma como base64 para que se vea en el preview sin depender de rutas públicas
        $office = $notificationBatch->office;
        $signatureUrl = null;
        if ($office?->signature) {
            $path = storage_path('app/public/'.$office->signature);
            if (file_exists($path)) {
                $mime = mime_content_type($path);
                $data = base64_encode(file_get_contents($path));
                $signatureUrl = "data:{$mime};base64,{$data}";
            }
        }

        // HTML real del email (idéntico al que se envía)
        $htmlEmail = view('emails.notification_batch', [
            'subject' => $notificationBatch->subject ?? 'Notificación de rubros vencidos',
            'body' => $htmlBody,
            'officeName' => $office?->name ?? '',
            'officeEmail' => $office?->email ?? '',
            'signatureUrl' => $signatureUrl,
            'sentAt' => now()->format('d/m/Y H:i'),
        ])->render();

        return response()->json([
            'subject' => $notificationBatch->subject ?? '',
            'emails' => $emails,
            'teachers' => $teachers,
            'html' => $htmlEmail,
        ]);
    }

    private function renderBody(string $text): string
    {
        $html = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $html = preg_replace('/\*([^*\n]+)\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/_([^_\n]+)_/', '<em>$1</em>', $html);

        $lines = explode("\n", $html);
        $lines = array_map(function ($line) {
            if (preg_match('/^- .+\(Ciclo: .+, Grupo: .+\)/', $line)) {
                $content = preg_replace('/^- /', '', $line);

                return '<span style="display:inline-flex;align-items:center;gap:6px;'
                    .'background:#f0f7ff;border-left:3px solid #0078d4;'
                    .'border-radius:4px;padding:2px 8px;margin:1px 0;'
                    .'font-size:0.8rem;color:#0078d4;font-weight:500;">'
                    .$content.'</span>';
            }

            return $line;
        }, $lines);

        return implode('<br>', $lines);
    }

    public function send(NotificationBatch $notificationBatch)
    {
        if (! $notificationBatch->notification_template_id) {
            return back()->with([
                'warning' => 'El lote no tiene plantilla asignada.',
            ]);
        }

        if (! $notificationBatch->office_id) {
            return back()->with([
                'warning' => 'El lote no tiene oficina asignada.',
            ]);
        }

        // Guardar estado anterior
        $previousStatus = $notificationBatch->status;

        // Bloquear si está completado y no quedan omitidos por falta de correo
        if ($previousStatus === NotificationBatch::STATUS_COMPLETED) {
            $hasSkipped = $notificationBatch->details()->where('status', 'skipped')->exists();
            if (! $hasSkipped) {
                return back()->with([
                    'warning' => 'Este lote ya fue enviado completamente y solo queda como historial.',
                ]);
            }
        }

        // Cambiar a processing
        $notificationBatch->update([
            'status' => NotificationBatch::STATUS_PROCESSING,
        ]);

        // Determinar si es reintento masivo (fallidos, omitidos o lote ya procesado anteriormente)
        $isRetry = in_array($previousStatus, [
            NotificationBatch::STATUS_COMPLETED,
            NotificationBatch::STATUS_COMPLETED_WITH_ERRORS,
        ]);

        // Ejecutar sincrónicamente (no requiere worker de colas)
        SendNotificationBatchJob::dispatchSync(
            $notificationBatch->id,
            $isRetry
        );

        return back()->with('success', 'Envío completado.');
    }

    public function resendDetail(NotificationBatchDetail $detail)
    {
        // Solo permitir si está fallido u omitido (sin correo)
        if (! in_array($detail->status, ['failed', 'skipped'])) {
            return back()->with([
                'warning' => 'Solo se pueden reenviar notificaciones fallidas u omitidas por falta de correo.',
            ]);
        }

        $batch = NotificationBatch::find($detail->notification_batch_id);

        // VALIDACIÓN NUEVA (AQUÍ EXACTAMENTE)
        $detail->load('teacher');

        if (! $detail->teacher || ! $detail->teacher->email) {
            return back()->with([
                'warning' => 'El docente no tiene correo registrado.',
            ]);
        }

        SendNotificationBatchJob::dispatchSync(
            $detail->notification_batch_id,
            false,
            $detail->id
        );

        return back()->with('success', 'Reenvío completado.');
    }

    public function show(NotificationBatch $notificationBatch)
    {
        $statusMap = [
            // Batch
            'draft' => 'Borrador',
            'active' => 'Activo',
            'processing' => 'Procesando',
            'completed' => 'Completado',
            'completed_with_errors' => 'Completado con errores',
            'cancelled' => 'Cancelado',

            // Detail
            'pending' => 'Pendiente',
            'sent' => 'Enviado',
            'failed' => 'Fallido',
            'skipped' => 'Sin correo',
        ];

        $notificationBatch->load([
            'academicPeriod',
            'campus',
            'office',
        ]);

        $details = $notificationBatch->details()
            ->with(['teacher' => fn ($q) => $q->withTrashed()])
            ->paginate(4)
            ->appends(request()->query());

        $details->getCollection()->transform(function ($detail) use ($statusMap) {
            return [
                'id' => $detail->id,
                'teacher' => [
                    'full_name' => optional($detail->teacher)->full_name ?? 'Docente no disponible',
                    'dni' => optional($detail->teacher)->dni ?? null,
                ],
                'has_email' => ! empty(optional($detail->teacher)->email),
                'pending_courses_count' => $detail->pending_courses_count,
                'status' => $detail->status,
                'status_label' => $statusMap[$detail->status] ?? $detail->status,
            ];
        });

        $detailsQuery = $notificationBatch->details();

        return response()->json([
            'id' => $notificationBatch->id,
            'name' => $notificationBatch->name,
            'status' => $notificationBatch->status,
            'status_label' => $statusMap[$notificationBatch->status] ?? $notificationBatch->status,
            'teachers_count' => $detailsQuery->count(),
            'total_pending_courses' => $detailsQuery->sum('pending_courses_count'),
            'sent_count' => $detailsQuery->where('status', 'sent')->count(),
            'failed_count' => $detailsQuery->where('status', 'failed')->count(),
            'skipped_count' => $detailsQuery->where('status', 'skipped')->count(),
            'pending_count' => $detailsQuery->where('status', 'pending')->count(),
            'academic_period' => [
                'name' => optional($notificationBatch->academicPeriod)->name,
            ],
            'campus' => [
                'name' => optional($notificationBatch->campus)->name,
            ],
            'office' => $notificationBatch->office ? [
                'id' => $notificationBatch->office->id,
                'name' => $notificationBatch->office->name,
                'email' => $notificationBatch->office->email,
                'signature' => $notificationBatch->office->signature,
            ] : null,
            'details' => $details,
        ]);
    }
}
