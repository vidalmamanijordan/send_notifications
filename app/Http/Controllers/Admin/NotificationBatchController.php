<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationBatchJob;
use App\Models\AcademicPeriod;
use App\Models\Campus;
use App\Models\NotificationBatch;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationBatchController extends Controller
{
    /**
     * Vista principal (SPA)
     */
    public function index(Request $request)
    {
        $query = NotificationBatch::with([
            'academicPeriod',
            'campus',
            'notificationTemplate'
        ]);

        if ($request->academic_period_id) {
            $query->where('academic_period_id', $request->academic_period_id);
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
            'templates' => NotificationTemplate::select('id', 'name')->get(), // ✅ NUEVO
            'filters' => $request->only('academic_period_id', 'campus_id', 'status')
        ]);
    }

    public function getTemplates()
    {
        return NotificationTemplate::select('id', 'name')->latest()->get();
    }

    public function attachTemplate(Request $request, NotificationBatch $notificationBatch)
    {
        $request->validate([
            'notification_template_id' => 'required|exists:notification_templates,id'
        ]);

        // 🔒 SOLO permitir si está en draft o active
        if (!in_array($notificationBatch->status, ['draft', 'active'])) {
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
            'status' => NotificationBatch::STATUS_ACTIVE
        ]);

        return back()->with('success', 'Plantilla asociada correctamente');
    }

    public function send(NotificationBatch $notificationBatch)
    {
        // 1. Validar que tenga plantilla
        if (!$notificationBatch->notification_template_id) {
            return back()->with([
                'warning' => 'El lote no tiene plantilla asignada.'
            ]);
        }

        // 2. Cambiar estado a processing
        $notificationBatch->update([
            'status' => NotificationBatch::STATUS_PROCESSING
        ]);

        // 3. Lanzar Job (lo crearemos en el siguiente paso)
        SendNotificationBatchJob::dispatch($notificationBatch->id);

        return back()->with('success', 'Envío iniciado.');
    }

    public function show(NotificationBatch $notificationBatch)
    {
        $statusMap = [
            // Batch
            'draft'       => 'Borrador',
            'active'      => 'Activo',
            'processing'  => 'Procesando',
            'completed'   => 'Completado',
            'completed_with_errors' => 'Completado con errores',
            'cancelled'   => 'Cancelado',

            // Detail
            'pending'     => 'Pendiente',
            'sent'        => 'Enviado',
            'failed'      => 'Fallido',
        ];

        $notificationBatch->load([
            'academicPeriod',
            'campus'
        ]);

        $details = $notificationBatch->details()
            ->with('teacher')
            ->paginate(4);

        $details->getCollection()->transform(function ($detail) use ($statusMap) {
            return [
                'id' => $detail->id,
                'teacher' => [
                    'full_name' => optional($detail->teacher)->full_name ?? 'Docente no disponible'
                ],
                'pending_courses_count' => $detail->pending_courses_count,
                'status' => $detail->status,
                'status_label' => $statusMap[$detail->status] ?? $detail->status,
            ];
        });

        return response()->json([
            'id' => $notificationBatch->id,
            'name' => $notificationBatch->name,
            'status' => $notificationBatch->status, // 🔥 ESTE ES EL QUE FALTA
            'status_label' => $statusMap[$notificationBatch->status] ?? $notificationBatch->status,
            'teachers_count' => $notificationBatch->details()->count(),
            'total_pending_courses' => $notificationBatch->details()->sum('pending_courses_count'),
            'academic_period' => [
                'name' => optional($notificationBatch->academicPeriod)->name
            ],
            'campus' => [
                'name' => optional($notificationBatch->campus)->name
            ],
            'details' => $details
        ]);
    }
}
