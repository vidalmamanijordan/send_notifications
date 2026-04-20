<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Campus;
use App\Models\NotificationBatch;
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

        // ── Filtros del tab Dashboard ────────────────────────────────────
        $campusId = $request->integer('campus_id') ?: null;
        $status = $request->get('status');

        // ── Filtros del tab Reporte ciclo ────────────────────────────────
        $cycleCampusId = $request->integer('cycle_campus_id') ?: null;
        $cycleFacultyId = $request->integer('cycle_faculty_id') ?: null;
        $cycleProgramId = $request->integer('cycle_program_id') ?: null;

        // Teacher IDs filtrados por facultad/programa (para el ciclo)
        $cycleTeacherFilter = null;
        if ($periodId && ($cycleFacultyId || $cycleProgramId)) {
            $cycleTeacherFilter = DB::table('teacher_assignments')
                ->where('academic_period_id', $periodId)
                ->whereNull('deleted_at')
                ->when($cycleFacultyId, fn ($q) => $q->where('faculty_id', $cycleFacultyId))
                ->when($cycleProgramId, fn ($q) => $q->where('program_id', $cycleProgramId))
                ->when($cycleCampusId, fn ($q) => $q->where('campus_id', $cycleCampusId))
                ->distinct()
                ->pluck('teacher_id');
        }

        // ── Correos enviados hoy ────────────────────────────────────────
        $sentToday = NotificationBatchDetail::where('status', 'sent')
            ->whereDate('sent_at', today())
            ->count();

        $remaining = max(0, self::DAILY_LIMIT - $sentToday);

        // ── Docentes únicos notificados hoy ────────────────────────────
        $notifiedToday = NotificationBatchDetail::where('status', 'sent')
            ->whereDate('sent_at', today())
            ->distinct('teacher_id')
            ->count('teacher_id');

        // ── Docentes pendientes de notificación (en lotes del periodo) ──
        $notifiedTodayIds = NotificationBatchDetail::where('status', 'sent')
            ->whereDate('sent_at', today())
            ->distinct()
            ->pluck('teacher_id');

        $pendingToday = NotificationBatchDetail::where('status', 'pending')
            ->whereHas('notificationBatch', function ($q) use ($periodId, $campusId) {
                $q->where('academic_period_id', $periodId);
                if ($campusId) {
                    $q->where('campus_id', $campusId);
                }
            })
            ->when($notifiedTodayIds->isNotEmpty(), fn ($q) => $q->whereNotIn('teacher_id', $notifiedTodayIds))
            ->distinct('teacher_id')
            ->count('teacher_id');

        // ── IDs de docentes notificados en el periodo (para gráficos) ──
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

        // ── Top docentes con más notificaciones en el ciclo ─────────────
        $topTeachersCycle = $periodId
            ? NotificationBatchDetail::select('teacher_id', DB::raw('COUNT(*) as total'))
                ->where('status', 'sent')
                ->whereHas('notificationBatch', function ($q) use ($periodId, $cycleCampusId) {
                    $q->where('academic_period_id', $periodId)
                        ->where('type', NotificationBatch::TYPE_RUBRICS);
                    if ($cycleCampusId) {
                        $q->where('campus_id', $cycleCampusId);
                    }
                })
                ->when(
                    $cycleTeacherFilter !== null,
                    fn ($q) => $q->whereIn('teacher_id', $cycleTeacherFilter->isEmpty() ? [0] : $cycleTeacherFilter)
                )
                ->with('teacher:id,full_name,dni,phone')
                ->groupBy('teacher_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(fn ($d) => [
                    'name' => $d->teacher?->full_name ?? '—',
                    'dni' => $d->teacher?->dni ?? '—',
                    'phone' => $d->teacher?->phone,
                    'total' => $d->total,
                ])
            : collect();

        // ── Notificaciones por facultad en el ciclo ─────────────────────
        $byFacultyNotifications = $periodId
            ? DB::table('notification_batch_details')
                ->join('notification_batches', 'notification_batch_details.notification_batch_id', '=', 'notification_batches.id')
                ->join('teacher_assignments', function ($join) use ($periodId) {
                    $join->on('notification_batch_details.teacher_id', '=', 'teacher_assignments.teacher_id')
                        ->where('teacher_assignments.academic_period_id', $periodId)
                        ->whereNull('teacher_assignments.deleted_at');
                })
                ->join('faculties', 'teacher_assignments.faculty_id', '=', 'faculties.id')
                ->where('notification_batch_details.status', 'sent')
                ->where('notification_batches.academic_period_id', $periodId)
                ->whereNull('notification_batches.deleted_at')
                ->when($cycleCampusId, fn ($q) => $q->where('notification_batches.campus_id', $cycleCampusId))
                ->when($cycleFacultyId, fn ($q) => $q->where('teacher_assignments.faculty_id', $cycleFacultyId))
                ->when($cycleProgramId, fn ($q) => $q->where('teacher_assignments.program_id', $cycleProgramId))
                ->select('faculties.name', DB::raw('COUNT(DISTINCT notification_batch_details.id) as total'))
                ->groupBy('faculties.id', 'faculties.name')
                ->orderByDesc('total')
                ->get()
            : collect();

        // ── Notificaciones por programa en el ciclo ─────────────────────
        $byProgramNotifications = $periodId
            ? DB::table('notification_batch_details')
                ->join('notification_batches', 'notification_batch_details.notification_batch_id', '=', 'notification_batches.id')
                ->join('teacher_assignments', function ($join) use ($periodId) {
                    $join->on('notification_batch_details.teacher_id', '=', 'teacher_assignments.teacher_id')
                        ->where('teacher_assignments.academic_period_id', $periodId)
                        ->whereNull('teacher_assignments.deleted_at');
                })
                ->join('programs', 'teacher_assignments.program_id', '=', 'programs.id')
                ->where('notification_batch_details.status', 'sent')
                ->where('notification_batches.academic_period_id', $periodId)
                ->whereNull('notification_batches.deleted_at')
                ->when($cycleCampusId, fn ($q) => $q->where('notification_batches.campus_id', $cycleCampusId))
                ->when($cycleFacultyId, fn ($q) => $q->where('teacher_assignments.faculty_id', $cycleFacultyId))
                ->when($cycleProgramId, fn ($q) => $q->where('teacher_assignments.program_id', $cycleProgramId))
                ->select('programs.name', DB::raw('COUNT(DISTINCT notification_batch_details.id) as total'))
                ->groupBy('programs.id', 'programs.name')
                ->orderByDesc('total')
                ->get()
            : collect();

        // ── Notificaciones por campus en el ciclo ─────────────────────
        $byCampusNotifications = $periodId
            ? DB::table('notification_batch_details')
                ->join('notification_batches', 'notification_batch_details.notification_batch_id', '=', 'notification_batches.id')
                ->join('campus', 'notification_batches.campus_id', '=', 'campus.id')
                ->where('notification_batch_details.status', 'sent')
                ->where('notification_batches.academic_period_id', $periodId)
                ->whereNull('notification_batches.deleted_at')
                ->when($cycleCampusId, fn ($q) => $q->where('notification_batches.campus_id', $cycleCampusId))
                ->when(
                    $cycleTeacherFilter !== null,
                    fn ($q) => $q->whereIn('notification_batch_details.teacher_id', $cycleTeacherFilter->isEmpty() ? [0] : $cycleTeacherFilter)
                )
                ->select('campus.name', DB::raw('COUNT(*) as total'))
                ->groupBy('campus.id', 'campus.name')
                ->orderByDesc('total')
                ->get()
            : collect();

        // ── Tabla detalle docentes notificados ──────────────────────────
        $teacherDetailReport = collect();

        if ($periodId) {
            $sentTeacherIds = DB::table('notification_batch_details')
                ->join('notification_batches', 'notification_batch_details.notification_batch_id', '=', 'notification_batches.id')
                ->where('notification_batch_details.status', 'sent')
                ->where('notification_batches.academic_period_id', $periodId)
                ->where('notification_batches.type', NotificationBatch::TYPE_RUBRICS)
                ->whereNull('notification_batches.deleted_at')
                ->when($cycleCampusId, fn ($q) => $q->where('notification_batches.campus_id', $cycleCampusId))
                ->distinct()
                ->pluck('notification_batch_details.teacher_id');

            // Aplicar filtro de facultad/programa
            if ($cycleTeacherFilter !== null) {
                $sentTeacherIds = $sentTeacherIds->intersect($cycleTeacherFilter);
            }

            if ($sentTeacherIds->isNotEmpty()) {
                $sentCounts = DB::table('notification_batch_details')
                    ->join('notification_batches', 'notification_batch_details.notification_batch_id', '=', 'notification_batches.id')
                    ->where('notification_batch_details.status', 'sent')
                    ->where('notification_batches.academic_period_id', $periodId)
                    ->where('notification_batches.type', NotificationBatch::TYPE_RUBRICS)
                    ->whereNull('notification_batches.deleted_at')
                    ->whereIn('notification_batch_details.teacher_id', $sentTeacherIds)
                    ->when($cycleCampusId, fn ($q) => $q->where('notification_batches.campus_id', $cycleCampusId))
                    ->select('notification_batch_details.teacher_id', DB::raw('COUNT(*) as total'))
                    ->groupBy('notification_batch_details.teacher_id')
                    ->pluck('total', 'teacher_id');

                $campusesPerTeacher = DB::table('notification_batch_details')
                    ->join('notification_batches', 'notification_batch_details.notification_batch_id', '=', 'notification_batches.id')
                    ->join('campus', 'notification_batches.campus_id', '=', 'campus.id')
                    ->where('notification_batch_details.status', 'sent')
                    ->where('notification_batches.academic_period_id', $periodId)
                    ->where('notification_batches.type', NotificationBatch::TYPE_RUBRICS)
                    ->whereNull('notification_batches.deleted_at')
                    ->whereIn('notification_batch_details.teacher_id', $sentTeacherIds)
                    ->when($cycleCampusId, fn ($q) => $q->where('notification_batches.campus_id', $cycleCampusId))
                    ->select('notification_batch_details.teacher_id', 'campus.name')
                    ->distinct()
                    ->get()
                    ->groupBy('teacher_id')
                    ->map(fn ($rows) => $rows->pluck('name')->values());

                $evalStatuses = NotificationBatchDetail::query()
                    ->getModel()
                    ->getConnection()
                    ->table('teacher_evaluation_status')
                    ->where('academic_period_id', $periodId)
                    ->whereIn('teacher_id', $sentTeacherIds)
                    ->whereNull('teacher_evaluation_status.deleted_at')
                    ->join('courses', 'teacher_evaluation_status.course_id', '=', 'courses.id')
                    ->join('campus', 'teacher_evaluation_status.campus_id', '=', 'campus.id')
                    ->select(
                        'teacher_evaluation_status.teacher_id',
                        'courses.name as course_name',
                        'campus.name as campus_name',
                        'teacher_evaluation_status.cycle',
                        'teacher_evaluation_status.group',
                        'teacher_evaluation_status.expired_components'
                    )
                    ->get()
                    ->groupBy('teacher_id');

                $teachers = \App\Models\Teacher::whereIn('id', $sentTeacherIds)
                    ->get(['id', 'full_name', 'dni']);

                $teacherDetailReport = $teachers->map(function ($teacher) use ($sentCounts, $campusesPerTeacher, $evalStatuses) {
                    $statuses = $evalStatuses->get($teacher->id, collect());
                    $courses = $statuses->map(fn ($s) => [
                        'name' => $s->course_name,
                        'campus' => $s->campus_name,
                        'cycle' => $s->cycle ?? '—',
                        'group' => $s->group ?? '—',
                        'expired' => (int) $s->expired_components,
                    ])->values();

                    return [
                        'name' => $teacher->full_name,
                        'dni' => $teacher->dni,
                        'campuses' => $campusesPerTeacher->get($teacher->id, collect())->toArray(),
                        'courses' => $courses->toArray(),
                        'total_expired' => $courses->sum('expired'),
                        'total_sent' => (int) $sentCounts->get($teacher->id, 0),
                    ];
                })->sortByDesc('total_sent')->values();
            }
        }

        // ── Listas para los selectores de filtros del ciclo ─────────────
        // Las relaciones campus→facultad→programa viven en teacher_assignments
        $facultyList = $periodId
            ? DB::table('teacher_assignments')
                ->join('faculties', 'teacher_assignments.faculty_id', '=', 'faculties.id')
                ->where('teacher_assignments.academic_period_id', $periodId)
                ->whereNull('teacher_assignments.deleted_at')
                ->whereNull('faculties.deleted_at')
                ->select('faculties.id', 'faculties.name', 'teacher_assignments.campus_id')
                ->distinct()
                ->orderBy('faculties.name')
                ->get()
                ->map(fn ($f) => ['id' => $f->id, 'name' => $f->name, 'campus_id' => $f->campus_id])
            : collect();

        $programList = $periodId
            ? DB::table('teacher_assignments')
                ->join('programs', 'teacher_assignments.program_id', '=', 'programs.id')
                ->where('teacher_assignments.academic_period_id', $periodId)
                ->whereNull('teacher_assignments.deleted_at')
                ->whereNull('programs.deleted_at')
                ->select('programs.id', 'programs.name', 'teacher_assignments.faculty_id', 'teacher_assignments.campus_id')
                ->distinct()
                ->orderBy('programs.name')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'faculty_id' => $p->faculty_id,
                    'campus_id' => $p->campus_id,
                ])
            : collect();

        return Inertia::render('admin/reports/Index', [
            'filters' => [
                'campus_id' => $campusId,
                'status' => $status,
                'cycle_campus_id' => $cycleCampusId,
                'cycle_faculty_id' => $cycleFacultyId,
                'cycle_program_id' => $cycleProgramId,
            ],
            'currentPeriodName' => $currentPeriodName,
            'hasPeriod' => (bool) $periodId,
            'campusList' => Campus::orderBy('name')->get(['id', 'name']),
            'facultyList' => $facultyList->values(),
            'programList' => $programList->values(),
            'dailyLimit' => self::DAILY_LIMIT,
            'sentToday' => $sentToday,
            'remaining' => $remaining,
            'notifiedToday' => $notifiedToday,
            'pendingToday' => $pendingToday,
            'totalNotified' => $totalNotified,
            'totalNotNotified' => $totalNotNotified,
            'byFaculty' => $byFaculty->values(),
            'byProgram' => $byProgram->values(),
            'topTeachers' => $topTeachers->values(),
            'topTeachersCycle' => $topTeachersCycle->values(),
            'byCampusNotifications' => $byCampusNotifications->values(),
            'byFacultyNotifications' => $byFacultyNotifications->values(),
            'byProgramNotifications' => $byProgramNotifications->values(),
            'teacherDetailReport' => $teacherDetailReport->values(),
        ]);
    }
}
