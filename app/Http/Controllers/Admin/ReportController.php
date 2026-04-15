<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Campus;
use App\Models\NotificationBatchDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public const DAILY_LIMIT = 500;

    public function index(Request $request): \Inertia\Response
    {
        // ── Periodo desde la sesión global (igual que el resto de vistas) ──
        $periodId = session('selected_period_id')
            ?: AcademicPeriod::where('status', 'active')->value('id');

        $currentPeriodName = $periodId
            ? AcademicPeriod::where('id', $periodId)->value('name')
            : null;

        $campusId = $request->integer('campus_id') ?: null;
        $status = $request->get('status'); // 'notified' | 'not_notified'

        // ── Correos enviados hoy ────────────────────────────────────────
        $sentToday = NotificationBatchDetail::where('status', 'sent')
            ->whereDate('sent_at', today())
            ->count();

        $remaining = max(0, self::DAILY_LIMIT - $sentToday);

        // ── IDs de docentes notificados en el periodo ───────────────────
        $notifiedSubquery = NotificationBatchDetail::select('teacher_id')
            ->where('status', 'sent')
            ->whereHas('notificationBatch', function ($q) use ($periodId, $campusId) {
                $q->where(function ($q2) use ($periodId) {
                    $q2->where('academic_period_id', $periodId)
                        ->orWhere('type', 'free');
                });
                if ($campusId) {
                    $q->where('campus_id', $campusId);
                }
            });

        // ── Métricas totales ────────────────────────────────────────────
        $totalNotified = (clone $notifiedSubquery)->distinct('teacher_id')->count();

        $totalInBatches = NotificationBatchDetail::whereHas('notificationBatch', function ($q) use ($periodId, $campusId) {
            $q->where(function ($q2) use ($periodId) {
                $q2->where('academic_period_id', $periodId)
                    ->orWhere('type', 'free');
            });
            if ($campusId) {
                $q->where('campus_id', $campusId);
            }
        })->distinct('teacher_id')->count();

        $totalNotNotified = max(0, $totalInBatches - $totalNotified);

        // ── Facultad con más docentes notificados ───────────────────────
        $notifiedIds = (clone $notifiedSubquery)->distinct()->pluck('teacher_id');

        $byFaculty = collect();
        $byProgram = collect();
        $topTeachers = collect();

        if ($periodId && $notifiedIds->isNotEmpty()) {
            $byFaculty = DB::table('teacher_assignments')
                ->join('faculties', 'teacher_assignments.faculty_id', '=', 'faculties.id')
                ->whereIn('teacher_assignments.teacher_id', $notifiedIds)
                ->where('teacher_assignments.academic_period_id', $periodId)
                ->when($campusId, fn ($q) => $q->where('teacher_assignments.campus_id', $campusId))
                ->whereNull('teacher_assignments.deleted_at')
                ->select('faculties.name', DB::raw('COUNT(DISTINCT teacher_assignments.teacher_id) as total'))
                ->groupBy('faculties.id', 'faculties.name')
                ->orderByDesc('total')
                ->limit(8)
                ->get();

            // ── Escuela profesional con más docentes notificados ────────
            $byProgram = DB::table('teacher_assignments')
                ->join('programs', 'teacher_assignments.program_id', '=', 'programs.id')
                ->whereIn('teacher_assignments.teacher_id', $notifiedIds)
                ->where('teacher_assignments.academic_period_id', $periodId)
                ->when($campusId, fn ($q) => $q->where('teacher_assignments.campus_id', $campusId))
                ->whereNull('teacher_assignments.deleted_at')
                ->select('programs.name', DB::raw('COUNT(DISTINCT teacher_assignments.teacher_id) as total'))
                ->groupBy('programs.id', 'programs.name')
                ->orderByDesc('total')
                ->limit(8)
                ->get();

            // ── Docente con mayor número de notificaciones ──────────────
            $topTeachers = NotificationBatchDetail::select('teacher_id', DB::raw('COUNT(*) as total'))
                ->where('status', 'sent')
                ->whereHas('notificationBatch', function ($q) use ($periodId, $campusId) {
                    $q->where(function ($q2) use ($periodId) {
                        $q2->where('academic_period_id', $periodId)
                            ->orWhere('type', 'free');
                    });
                    if ($campusId) {
                        $q->where('campus_id', $campusId);
                    }
                })
                ->with('teacher:id,full_name,dni')
                ->groupBy('teacher_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn ($d) => [
                    'name' => $d->teacher?->full_name ?? '—',
                    'dni' => $d->teacher?->dni ?? '—',
                    'total' => $d->total,
                ]);
        }

        return Inertia::render('admin/reports/Index', [
            'filters' => [
                'campus_id' => $campusId,
                'status' => $status,
            ],
            'currentPeriodName' => $currentPeriodName,
            'hasPeriod' => (bool) $periodId,
            'campusList' => Campus::orderBy('name')->get(['id', 'name']),
            'dailyLimit' => self::DAILY_LIMIT,
            'sentToday' => $sentToday,
            'remaining' => $remaining,
            'totalNotified' => $totalNotified,
            'totalNotNotified' => $totalNotNotified,
            'byFaculty' => $byFaculty->values(),
            'byProgram' => $byProgram->values(),
            'topTeachers' => $topTeachers->values(),
        ]);
    }
}
