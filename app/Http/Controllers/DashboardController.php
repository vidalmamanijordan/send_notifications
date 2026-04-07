<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\NotificationBatch;
use App\Models\NotificationBatchDetail;
use App\Models\Teacher;
use App\Models\TeacherEvaluationStatus;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $periodId = session('selected_period_id')
            ?? AcademicPeriod::where('status', 'active')->value('id');

        $period = $periodId
            ? AcademicPeriod::find($periodId, ['id', 'name', 'status'])
            : null;

        /* ── KPIs ─────────────────────────────────────────── */
        $totalBatches = NotificationBatch::when($periodId, fn ($q) => $q->where('academic_period_id', $periodId))->count();

        $detailBase = NotificationBatchDetail::when(
            $periodId,
            fn ($q) => $q->whereHas(
                'notificationBatch',
                fn ($q) => $q->where('academic_period_id', $periodId)
            )
        );

        $sentCount    = (clone $detailBase)->where('status', 'sent')->count();
        $failedCount  = (clone $detailBase)->where('status', 'failed')->count();
        $skippedCount = (clone $detailBase)->where('status', 'skipped')->count();
        $pendingCount = (clone $detailBase)->where('status', 'pending')->count();
        $totalDetails = $sentCount + $failedCount + $skippedCount + $pendingCount;
        $actionable   = $sentCount + $failedCount;
        $successRate  = $actionable > 0 ? round($sentCount / $actionable * 100, 1) : 0;

        $totalTeachers = Teacher::count();
        $notifiedTeachers = (clone $detailBase)->where('status', 'sent')
            ->distinct('teacher_id')->count('teacher_id');

        /* ── Donut: notificaciones por estado ─────────────── */
        $statusLabels = [
            'sent'    => 'Enviados',
            'failed'  => 'Fallidos',
            'skipped' => 'Sin correo',
            'pending' => 'Pendientes',
        ];

        $detailsByStatus = (clone $detailBase)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $donutSeries = collect($statusLabels)->map(fn ($_, $key) => (int) ($detailsByStatus[$key] ?? 0))->values()->toArray();
        $donutLabels = collect($statusLabels)->values()->toArray();

        /* ── Barras: lotes por estado ─────────────────────── */
        $batchStatusLabels = [
            'draft'                  => 'Borrador',
            'active'                 => 'Activo',
            'processing'             => 'Procesando',
            'completed'              => 'Completado',
            'completed_with_errors'  => 'Con errores',
            'cancelled'              => 'Cancelado',
        ];

        $batchesByStatus = NotificationBatch::when($periodId, fn ($q) => $q->where('academic_period_id', $periodId))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $batchBarCategories = collect($batchStatusLabels)->values()->toArray();
        $batchBarData       = collect($batchStatusLabels)->map(fn ($_, $key) => (int) ($batchesByStatus[$key] ?? 0))->values()->toArray();

        /* ── Área: tendencia de envíos por día ────────────── */
        $trendRaw = (clone $detailBase)
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->selectRaw('DATE(sent_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $trendDates  = $trendRaw->pluck('day')->toArray();
        $trendValues = $trendRaw->pluck('total')->map(fn ($v) => (int) $v)->toArray();

        /* ── Top docentes con más vencidos ────────────────── */
        $topExpired = TeacherEvaluationStatus::when($periodId, fn ($q) => $q->where('academic_period_id', $periodId))
            ->select('teacher_id', DB::raw('SUM(expired_components) as total_expired'))
            ->groupBy('teacher_id')
            ->orderByDesc('total_expired')
            ->limit(8)
            ->with('teacher:id,full_name')
            ->get()
            ->map(fn ($r) => [
                'name'    => $r->teacher?->full_name ?? 'Desconocido',
                'expired' => (int) $r->total_expired,
            ]);

        /* ── Notificaciones por campus ────────────────────── */
        $byCampus = NotificationBatchDetail::when(
            $periodId,
            fn ($q) => $q->whereHas(
                'notificationBatch',
                fn ($q) => $q->where('academic_period_id', $periodId)
            )
        )
            ->join('notification_batches', 'notification_batch_details.notification_batch_id', '=', 'notification_batches.id')
            ->join('campus', 'notification_batches.campus_id', '=', 'campus.id')
            ->selectRaw('campus.name as campus_name,
                SUM(CASE WHEN notification_batch_details.status = "sent"   THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN notification_batch_details.status = "failed" THEN 1 ELSE 0 END) as failed')
            ->groupBy('campus.name')
            ->orderByDesc('sent')
            ->get();

        $campusCategories = $byCampus->pluck('campus_name')->toArray();
        $campusSent       = $byCampus->pluck('sent')->map(fn ($v) => (int) $v)->toArray();
        $campusFailed     = $byCampus->pluck('failed')->map(fn ($v) => (int) $v)->toArray();

        /* ── Evaluaciones por campus (vencidos vs evaluados) ─ */
        $evalByCampus = TeacherEvaluationStatus::when($periodId, fn ($q) => $q->where('academic_period_id', $periodId))
            ->join('campus', 'teacher_evaluation_status.campus_id', '=', 'campus.id')
            ->selectRaw('campus.name as campus_name,
                SUM(evaluated_components) as evaluated,
                SUM(expired_components)   as expired')
            ->groupBy('campus.name')
            ->orderByDesc('expired')
            ->get();

        $evalCampusCategories = $evalByCampus->pluck('campus_name')->toArray();
        $evalCampusEvaluated  = $evalByCampus->pluck('evaluated')->map(fn ($v) => (int) $v)->toArray();
        $evalCampusExpired    = $evalByCampus->pluck('expired')->map(fn ($v) => (int) $v)->toArray();

        return Inertia::render('Dashboard', [
            'period'           => $period,
            'kpis' => [
                'total_batches'     => $totalBatches,
                'sent'              => $sentCount,
                'failed'            => $failedCount,
                'skipped'           => $skippedCount,
                'pending'           => $pendingCount,
                'total_details'     => $totalDetails,
                'success_rate'      => $successRate,
                'total_teachers'    => $totalTeachers,
                'notified_teachers' => $notifiedTeachers,
            ],
            'charts' => [
                'donut'  => ['series' => $donutSeries, 'labels' => $donutLabels],
                'batches_by_status' => ['categories' => $batchBarCategories, 'data' => $batchBarData],
                'trend'  => ['dates' => $trendDates, 'values' => $trendValues],
                'top_expired' => $topExpired->values(),
                'by_campus'   => [
                    'categories' => $campusCategories,
                    'sent'       => $campusSent,
                    'failed'     => $campusFailed,
                ],
                'eval_by_campus' => [
                    'categories' => $evalCampusCategories,
                    'evaluated'  => $evalCampusEvaluated,
                    'expired'    => $evalCampusExpired,
                ],
            ],
        ]);
    }
}
