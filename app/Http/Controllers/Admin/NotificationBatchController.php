<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Campus;
use App\Models\NotificationBatch;
use App\Services\NotificationBatchBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationBatchController extends Controller
{
    /**
     * Vista principal (SPA)
     */
    public function index()
    {
        return Inertia::render('admin/notifications/Index', [
            'batches' => NotificationBatch::with(['academicPeriod', 'campus'])
                ->latest()
                ->paginate(10),

            'academicPeriods' => AcademicPeriod::select('id', 'name')->get(),
            'campus' => Campus::select('id', 'name')->get()
        ]);
    }

    /**
     * Construcción de lote (acción que modifica estado)
     * → debe REDIRIGIR como Programs/Campus
     */
    public function build(Request $request, NotificationBatchBuilder $builder)
    {
        $request->validate([
            'academic_period_id' => 'required|exists:academic_periods,id',
            'campus_id' => 'required|exists:campus,id'
        ]);

        $batch = $builder->build(
            $request->academic_period_id,
            $request->campus_id
        );

        if (!$batch) {
            return redirect()
                ->route('admin.notification-batches.index')
                ->with('error', 'No existen docentes con rubros vencidos');
        }

        return redirect()
            ->route('admin.notification-batches.index')
            ->with('success', 'Lote construido correctamente');
    }

    /**
     * Mostrar detalle del lote
     * ⚠️ IMPORTANTE:
     * NO usar Inertia::render aquí
     * → El modal necesita JSON
     * → Evitamos navegación SPA
     */
    public function show(NotificationBatch $notificationBatch)
    {
        $statusMap = [
            'draft'     => 'Borrador',
            'ready'     => 'Listo para envío',
            'sending'   => 'Enviando',
            'sent'      => 'Enviado',
            'partial'   => 'Enviado parcialmente',
            'cancelled' => 'Cancelado',
            'pending'   => 'Pendiente',
            'notified'  => 'Notificado',
            'error'     => 'Error',
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

                'status_label' => $statusMap[$detail->status] ?? $detail->status,
            ];
        });

        return response()->json([
            'id' => $notificationBatch->id,
            'name' => $notificationBatch->name,

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
