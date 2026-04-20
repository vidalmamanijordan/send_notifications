<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    BarChart2,
    BookOpen,
    Building2,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    FileSpreadsheet,
    FileText,
    Filter,
    GraduationCap,
    Mail,
    TrendingUp,
    Users,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import * as XLSX from 'xlsx';

/* =========================
   Props
========================= */
const props = defineProps<{
    currentPeriodName: string | null;
    hasPeriod: boolean;
    campusList: { id: number; name: string }[];
    facultyList: { id: number; name: string; campus_id: number }[];
    programList: { id: number; name: string; faculty_id: number; campus_id: number }[];
    filters: {
        campus_id: number | null;
        status: string | null;
        cycle_campus_id: number | null;
        cycle_faculty_id: number | null;
        cycle_program_id: number | null;
    };
    dailyLimit: number;
    sentToday: number;
    remaining: number;
    notifiedToday: number;
    pendingToday: number;
    totalNotified: number;
    totalNotNotified: number;
    byFaculty: { name: string; total: number }[];
    byProgram: { name: string; total: number }[];
    topTeachers: { name: string; dni: string; total: number }[];
    topTeachersCycle: { name: string; dni: string; phone: string | null; total: number }[];
    byCampusNotifications: { name: string; total: number }[];
    byFacultyNotifications: { name: string; total: number }[];
    byProgramNotifications: { name: string; total: number }[];
    teacherDetailReport: {
        name: string;
        dni: string;
        campuses: string[];
        courses: { name: string; campus: string; cycle: string; group: string; expired: number }[];
        total_expired: number;
        total_sent: number;
    }[];
}>();

/* =========================
   Tabs
========================= */
const activeTab = ref<'dashboard' | 'cycle'>('dashboard');

/* =========================
   Filtros – Reporte ciclo
========================= */
const cycleCampusId  = ref<number | null>(props.filters.cycle_campus_id ?? null);
const cycleFacultyId = ref<number | null>(props.filters.cycle_faculty_id ?? null);
const cycleProgramId = ref<number | null>(props.filters.cycle_program_id ?? null);

// Cascada: facultades disponibles según campus seleccionado
const filteredFacultyList = computed(() => {
    const seen = new Set<number>();
    return props.facultyList.filter((f) => {
        // Coerción a número para evitar comparación string vs number en el select nativo
        if (cycleCampusId.value && Number(f.campus_id) !== Number(cycleCampusId.value)) return false;
        if (seen.has(f.id)) return false;
        seen.add(f.id);
        return true;
    });
});

// Cascada: programas disponibles según campus y facultad seleccionados
const filteredProgramList = computed(() => {
    const seen = new Set<number>();
    return props.programList.filter((p) => {
        if (cycleCampusId.value && Number(p.campus_id) !== Number(cycleCampusId.value)) return false;
        if (cycleFacultyId.value && Number(p.faculty_id) !== Number(cycleFacultyId.value)) return false;
        if (seen.has(p.id)) return false;
        seen.add(p.id);
        return true;
    });
});

// Handlers de cascada (evitan múltiples disparos de router.get)
const onCampusChange = () => {
    cycleFacultyId.value = null;
    cycleProgramId.value = null;
    applyCycleFilters();
};

const onFacultyChange = () => {
    cycleProgramId.value = null;
    applyCycleFilters();
};

const activeCycleFiltersCount = computed(() =>
    [cycleCampusId.value, cycleFacultyId.value, cycleProgramId.value].filter(Boolean).length
);

const cycleFilterLabel = computed(() => {
    if (cycleProgramId.value) {
        const p = props.programList.find((x) => x.id === cycleProgramId.value);
        return p ? `Programa: ${p.name}` : 'Programa seleccionado';
    }
    if (cycleFacultyId.value) {
        const f = props.facultyList.find((x) => x.id === cycleFacultyId.value);
        return f ? `Facultad: ${f.name}` : 'Facultad seleccionada';
    }
    if (cycleCampusId.value) {
        const c = props.campusList.find((x) => x.id === cycleCampusId.value);
        return c ? `Campus: ${c.name}` : 'Campus seleccionado';
    }
    return 'Todo el ciclo';
});

const applyCycleFilters = () => {
    router.get(
        route('admin.reports.index'),
        {
            cycle_campus_id:  cycleCampusId.value  ?? undefined,
            cycle_faculty_id: cycleFacultyId.value ?? undefined,
            cycle_program_id: cycleProgramId.value ?? undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const clearCycleFilters = () => {
    cycleCampusId.value  = null;
    cycleFacultyId.value = null;
    cycleProgramId.value = null;
    applyCycleFilters();
};

/* =========================
   Daily gauge
========================= */
const sentPercent = computed(() =>
    props.dailyLimit > 0
        ? Math.min(100, Math.round((props.sentToday / props.dailyLimit) * 100))
        : 0,
);

const gaugeColor = computed(() => {
    if (sentPercent.value >= 90)
        return {
            bar: '#dc2626',
            ring: 'ring-red-200',
            bg: 'bg-red-50',
            text: 'text-red-600',
            label: 'text-red-400',
            dark: 'dark:bg-red-900/20',
        };
    if (sentPercent.value >= 70)
        return {
            bar: '#f59e0b',
            ring: 'ring-amber-200',
            bg: 'bg-amber-50',
            text: 'text-amber-600',
            label: 'text-amber-400',
            dark: 'dark:bg-amber-900/20',
        };
    return {
        bar: '#10b981',
        ring: 'ring-emerald-200',
        bg: 'bg-emerald-50',
        text: 'text-emerald-600',
        label: 'text-emerald-500',
        dark: 'dark:bg-emerald-900/20',
    };
});

const gaugeStatus = computed(() => {
    if (sentPercent.value >= 90) return 'Límite casi alcanzado';
    if (sentPercent.value >= 70) return 'Uso elevado';
    return 'Uso normal';
});

/* =========================
   Cycle chart
========================= */
const isDark = ref(document.documentElement.classList.contains('dark'));

const cycleChartColors = ['#dc2626', '#ef4444', '#f87171', '#fca5a5', '#fecaca'];

const cycleChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: {
            show: true,
            tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false },
        },
        sparkline: { enabled: false },
        fontFamily: "'Inter', 'ui-sans-serif', system-ui, sans-serif",
        foreColor: isDark.value ? '#94a3b8' : '#64748b',
        animations: { enabled: true, speed: 600, animateGradually: { enabled: true, delay: 80 } },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 6,
            barHeight: '52%',
            distributed: true,
            dataLabels: { position: 'top' },
        },
    },
    colors: cycleChartColors,
    dataLabels: {
        enabled: true,
        offsetX: 4,
        style: { fontSize: '11px', fontWeight: 600, colors: [isDark.value ? '#cbd5e1' : '#334155'] },
    },
    legend: { show: false },
    xaxis: {
        categories: props.topTeachersCycle.map((t) => [t.name, `DNI: ${t.dni}`]),
        max: props.topTeachersCycle.length > 0
            ? props.topTeachersCycle[0].total * 1.2
            : undefined,
        labels: { style: { fontSize: '12px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { fontSize: '12px' },
            maxWidth: 220,
        },
    },
    grid: {
        borderColor: isDark.value ? '#1e293b' : '#f1f5f9',
        strokeDashArray: 4,
        xaxis: { lines: { show: true } },
        yaxis: { lines: { show: false } },
        padding: { right: 10 },
    },
    tooltip: {
        y: { formatter: (val: number) => `${val} notificaciones` },
    },
}));

const cycleChartSeries = computed(() => [
    { name: 'Notificaciones', data: props.topTeachersCycle.map((t) => t.total) },
]);

const campusColorMap: Record<string, string> = {
    Tarapoto: '#16a34a',
    Juliaca:  '#087ab1',
    Lima:     '#6b1030',
};

const campusColors = computed(() =>
    props.byCampusNotifications.map((c) => campusColorMap[c.name] ?? '#6b7280'),
);

const campusChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        fontFamily: "'Inter', 'ui-sans-serif', system-ui, sans-serif",
        foreColor: isDark.value ? '#94a3b8' : '#64748b',
        animations: { enabled: true, speed: 600 },
    },
    plotOptions: {
        bar: {
            borderRadius: 5,
            columnWidth: '55%',
            distributed: true,
            dataLabels: { position: 'center' },
        },
    },
    colors: campusColors.value,
    dataLabels: {
        enabled: true,
        offsetY: 0,
        style: { fontSize: '12px', fontWeight: 700, colors: ['#ffffff'] },
    },
    legend: { show: false },
    xaxis: {
        categories: props.byCampusNotifications.map((c) => c.name),
        labels: { style: { fontSize: '11px' }, rotate: -20 },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { labels: { style: { fontSize: '11px' } } },
    grid: {
        borderColor: isDark.value ? '#1e293b' : '#f1f5f9',
        strokeDashArray: 4,
        padding: { top: 10 },
    },
    tooltip: { y: { formatter: (val: number) => `${val} notificaciones` } },
}));

const campusChartSeries = computed(() => [
    { name: 'Notificaciones', data: props.byCampusNotifications.map((c) => c.total) },
]);

const facultyColors = [
    '#087ab1', '#0ea5e9', '#6366f1', '#8b5cf6',
    '#14b8a6', '#f59e0b', '#ef4444', '#10b981',
];

const facultyChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        fontFamily: "'Inter', 'ui-sans-serif', system-ui, sans-serif",
        foreColor: isDark.value ? '#94a3b8' : '#64748b',
        animations: { enabled: true, speed: 600 },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 5,
            barHeight: '55%',
            distributed: true,
            dataLabels: { position: 'center' },
        },
    },
    colors: facultyColors,
    dataLabels: {
        enabled: true,
        offsetX: 0,
        style: { fontSize: '11px', fontWeight: 700, colors: ['#ffffff'] },
    },
    legend: { show: false },
    xaxis: {
        categories: props.byFacultyNotifications.map((f) => f.name),
        labels: { style: { fontSize: '11px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: { style: { fontSize: '11px' }, maxWidth: 180 },
    },
    grid: {
        borderColor: isDark.value ? '#1e293b' : '#f1f5f9',
        strokeDashArray: 4,
        xaxis: { lines: { show: true } },
        yaxis: { lines: { show: false } },
        padding: { right: 10 },
    },
    tooltip: { y: { formatter: (val: number) => `${val} notificaciones` } },
}));

const facultyChartSeries = computed(() => [
    { name: 'Notificaciones', data: props.byFacultyNotifications.map((f) => f.total) },
]);

const programChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        fontFamily: "'Inter', 'ui-sans-serif', system-ui, sans-serif",
        foreColor: isDark.value ? '#94a3b8' : '#64748b',
        animations: { enabled: true, speed: 600 },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 5,
            barHeight: '55%',
            distributed: true,
            dataLabels: { position: 'center' },
        },
    },
    colors: [
        '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
        '#0ea5e9', '#f97316', '#14b8a6', '#ec4899',
        '#6366f1', '#84cc16',
    ],
    dataLabels: {
        enabled: true,
        offsetX: 0,
        style: { fontSize: '11px', fontWeight: 700, colors: ['#ffffff'] },
    },
    legend: { show: false },
    xaxis: {
        categories: props.byProgramNotifications.map((p) => p.name),
        labels: { style: { fontSize: '11px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: { style: { fontSize: '11px' }, maxWidth: 200 },
    },
    grid: {
        borderColor: isDark.value ? '#1e293b' : '#f1f5f9',
        strokeDashArray: 4,
        xaxis: { lines: { show: true } },
        yaxis: { lines: { show: false } },
        padding: { right: 10 },
    },
    tooltip: { y: { formatter: (val: number) => `${val} notificaciones` } },
}));

const programChartSeries = computed(() => [
    { name: 'Notificaciones', data: props.byProgramNotifications.map((p) => p.total) },
]);

/* =========================
   Tabla docentes — paginación
========================= */
const tablePage = ref(1);
const TABLE_PAGE_SIZE = 10;

const tablePaginated = computed(() => {
    const start = (tablePage.value - 1) * TABLE_PAGE_SIZE;
    return props.teacherDetailReport.slice(start, start + TABLE_PAGE_SIZE);
});

const tableTotalPages = computed(() =>
    Math.ceil(props.teacherDetailReport.length / TABLE_PAGE_SIZE),
);

const tableFrom = computed(() => (tablePage.value - 1) * TABLE_PAGE_SIZE + 1);
const tableTo = computed(() =>
    Math.min(tablePage.value * TABLE_PAGE_SIZE, props.teacherDetailReport.length),
);

/* =========================
   Tabla docentes — exportación
========================= */
const downloadTeacherExcel = () => {
    const headers = ['#', 'Docente', 'DNI', 'Campus', 'Curso', 'Ciclo', 'Grupo', 'Rúbros vencidos', 'Total notificaciones'];
    const rows: (string | number)[][] = [];

    props.teacherDetailReport.forEach((teacher, i) => {
        if (teacher.courses.length === 0) {
            rows.push([i + 1, teacher.name, teacher.dni, teacher.campuses.join(' / '), '—', '—', '—', teacher.total_expired, teacher.total_sent]);
        } else {
            teacher.courses.forEach((course, ci) => {
                rows.push([
                    ci === 0 ? i + 1 : '',
                    ci === 0 ? teacher.name : '',
                    ci === 0 ? teacher.dni : '',
                    ci === 0 ? teacher.campuses.join(' / ') : '',
                    course.name,
                    course.cycle,
                    course.group,
                    course.expired,
                    ci === 0 ? teacher.total_sent : '',
                ]);
            });
        }
    });

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);

    // Ancho de columnas
    ws['!cols'] = [
        { wch: 4 },  // #
        { wch: 36 }, // Docente
        { wch: 12 }, // DNI
        { wch: 22 }, // Campus
        { wch: 40 }, // Curso
        { wch: 10 }, // Ciclo
        { wch: 12 }, // Grupo
        { wch: 16 }, // Rúbros vencidos
        { wch: 20 }, // Total notificaciones
    ];

    XLSX.utils.book_append_sheet(wb, ws, 'Docentes Notificados');
    XLSX.writeFile(wb, `docentes-notificados-${props.currentPeriodName ?? 'ciclo'}.xlsx`);
};

const downloadTeacherPdf = () => {
    const win = window.open('', '_blank');
    if (!win) return;

    const rows = props.teacherDetailReport.map((teacher, i) => {
        const campuses = teacher.campuses.join(', ');
        const courses = teacher.courses.length
            ? teacher.courses.map((c) => `${c.name} &nbsp;<em>Ciclo ${c.cycle} · ${c.group}</em> <b>[${c.expired}]</b>`).join('<br>')
            : '—';
        return `<tr>
            <td>${i + 1}</td>
            <td><strong>${teacher.name}</strong><br><small style="color:#6b7280">DNI: ${teacher.dni}</small></td>
            <td>${campuses}</td>
            <td style="font-size:11px">${courses}</td>
            <td style="text-align:center">${teacher.total_expired}</td>
            <td style="text-align:center;font-weight:700;color:#087ab1">${teacher.total_sent}</td>
        </tr>`;
    }).join('');

    win.document.write(`<!DOCTYPE html>
<html><head><meta charset="utf-8">
<title>Docentes Notificados – ${props.currentPeriodName ?? ''}</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; margin: 24px; color: #111; }
  h2 { color: #087ab1; margin-bottom: 4px; }
  p  { margin: 0 0 12px; color: #6b7280; font-size: 11px; }
  table { width: 100%; border-collapse: collapse; }
  thead th { background: #087ab1; color: #fff; padding: 7px 10px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; }
  tbody td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
  tbody tr:nth-child(even) { background: #f9fafb; }
  @media print { body { margin: 0; } }
</style></head>
<body>
<h2>Detalle de docentes notificados</h2>
<p>Ciclo académico: <strong>${props.currentPeriodName ?? ''}</strong> &nbsp;·&nbsp; ${props.teacherDetailReport.length} docentes &nbsp;·&nbsp; Solo rúbros vencidos</p>
<table>
  <thead><tr><th>#</th><th>Docente</th><th>Campus</th><th>Cursos</th><th>Rúbros venc.</th><th>Notif.</th></tr></thead>
  <tbody>${rows}</tbody>
</table>
</body></html>`);
    win.document.close();
    win.print();
};

const downloadCsv = () => {
    const bom = '\uFEFF';
    const headers = ['Posición', 'Nombre', 'DNI', 'Celular', 'Notificaciones'];
    const rows = props.topTeachersCycle.map((t, i) => [
        i + 1,
        `"${t.name}"`,
        t.dni,
        t.phone ?? '',
        t.total,
    ]);
    const csv = bom + [headers, ...rows].map((r) => r.join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `top-docentes-${props.currentPeriodName ?? 'ciclo'}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};
</script>

<template>
    <Head title="Reportes" />

    <AppLayout>
        <div class="space-y-6 px-6 py-6">
            <!-- HEADER -->
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#087ab1] shadow-md"
                >
                    <BarChart2 class="h-5 w-5 text-white" />
                </div>
                <div>
                    <h1
                        class="text-xl font-bold text-gray-900 dark:text-gray-100"
                    >
                        Reportes
                    </h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Dashboard de métricas de notificaciones
                        <template v-if="currentPeriodName">
                            —
                            <span class="font-medium text-[#087ab1]">{{
                                currentPeriodName
                            }}</span>
                        </template>
                    </p>
                </div>
            </div>

            <!-- TABS CONTAINER -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <!-- TAB HEADERS -->
                <div class="flex border-b border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        @click="activeTab = 'dashboard'"
                        class="relative flex items-center gap-2 px-6 py-4 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'dashboard'
                                ? 'border-b-2 border-[#087ab1] text-[#087ab1]'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                    >
                        <BarChart2 class="h-4 w-4" />
                        Ahora mismo
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'cycle'"
                        class="relative flex items-center gap-2 px-6 py-4 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'cycle'
                                ? 'border-b-2 border-[#087ab1] text-[#087ab1]'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                    >
                        <ClipboardList class="h-4 w-4" />
                        Reporte ciclo
                    </button>
                </div>

                <!-- ══════════════════════════════════════
                     TAB 1: DASHBOARD
                ══════════════════════════════════════ -->
                <div v-if="activeTab === 'dashboard'" class="p-6">
                    <!-- SIN PERIODO -->
                    <div
                        v-if="!hasPeriod"
                        class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 py-20 text-center dark:border-gray-700 dark:bg-gray-900/40"
                    >
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10"
                        >
                            <BarChart2 class="h-7 w-7 text-[#087ab1]/40" />
                        </div>
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        >
                            No hay un periodo académico activo.<br />
                            Selecciona uno desde el selector del encabezado.
                        </p>
                    </div>

                    <template v-else>
                        <div class="space-y-5">
                            <!-- ── STAT CARDS ── -->
                            <div
                                class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                            >
                                <!-- Enviados hoy -->
                                <div
                                    class="group relative overflow-hidden rounded-2xl border border-emerald-100 bg-linear-to-br from-emerald-50 to-white p-5 shadow-sm transition hover:shadow-md dark:border-emerald-900/30 dark:from-emerald-900/20 dark:to-gray-800"
                                >
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div>
                                            <p
                                                class="text-xs font-medium tracking-wide text-emerald-600 uppercase dark:text-emerald-400"
                                            >
                                                Enviados hoy
                                            </p>
                                            <p
                                                class="mt-1.5 text-4xl font-extrabold text-emerald-700 dark:text-emerald-300"
                                            >
                                                {{ sentToday }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-gray-400"
                                            >
                                                de
                                                <span
                                                    class="font-semibold text-gray-600 dark:text-gray-300"
                                                    >{{ dailyLimit }}</span
                                                >
                                                correos permitidos
                                            </p>
                                        </div>
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-100 shadow-inner dark:bg-emerald-800/40"
                                        >
                                            <Mail
                                                class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Restantes hoy -->
                                <div
                                    class="group relative overflow-hidden rounded-2xl border p-5 shadow-sm transition hover:shadow-md"
                                    :class="
                                        remaining === 0
                                            ? 'border-red-100 bg-linear-to-br from-red-50 to-white dark:border-red-900/30 dark:from-red-900/20 dark:to-gray-800'
                                            : 'border-sky-100 bg-linear-to-br from-sky-50 to-white dark:border-sky-900/30 dark:from-sky-900/20 dark:to-gray-800'
                                    "
                                >
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div>
                                            <p
                                                class="text-xs font-medium tracking-wide uppercase"
                                                :class="
                                                    remaining === 0
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : 'text-sky-600 dark:text-sky-400'
                                                "
                                            >
                                                Restantes hoy
                                            </p>
                                            <p
                                                class="mt-1.5 text-4xl font-extrabold"
                                                :class="
                                                    remaining === 0
                                                        ? 'text-red-700 dark:text-red-300'
                                                        : 'text-sky-700 dark:text-sky-300'
                                                "
                                            >
                                                {{ remaining }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs"
                                                :class="
                                                    remaining === 0
                                                        ? 'text-red-400'
                                                        : 'text-gray-400'
                                                "
                                            >
                                                {{
                                                    remaining === 0
                                                        ? 'Límite alcanzado'
                                                        : `${sentPercent}% del límite usado`
                                                }}
                                            </p>
                                        </div>
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl shadow-inner"
                                            :class="
                                                remaining === 0
                                                    ? 'bg-red-100 dark:bg-red-800/40'
                                                    : 'bg-sky-100 dark:bg-sky-800/40'
                                            "
                                        >
                                            <TrendingUp
                                                class="h-5 w-5"
                                                :class="
                                                    remaining === 0
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : 'text-sky-600 dark:text-sky-400'
                                                "
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Notificados hoy -->
                                <div
                                    class="group relative overflow-hidden rounded-2xl border border-violet-100 bg-linear-to-br from-violet-50 to-white p-5 shadow-sm transition hover:shadow-md dark:border-violet-900/30 dark:from-violet-900/20 dark:to-gray-800"
                                >
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="text-xs font-medium tracking-wide text-violet-600 uppercase dark:text-violet-400"
                                            >
                                                Notificados hoy
                                            </p>
                                            <p
                                                class="mt-1.5 text-4xl font-extrabold text-violet-700 dark:text-violet-300"
                                            >
                                                {{ notifiedToday }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-gray-400"
                                            >
                                                docentes únicos alcanzados hoy
                                            </p>
                                        </div>
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 shadow-inner dark:bg-violet-800/40"
                                        >
                                            <CheckCircle2
                                                class="h-5 w-5 text-violet-600 dark:text-violet-400"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Pendientes hoy -->
                                <div
                                    class="group relative overflow-hidden rounded-2xl border border-amber-100 bg-linear-to-br from-amber-50 to-white p-5 shadow-sm transition hover:shadow-md dark:border-amber-900/30 dark:from-amber-900/20 dark:to-gray-800"
                                >
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="text-xs font-medium tracking-wide text-amber-600 uppercase dark:text-amber-400"
                                            >
                                                Pendientes hoy
                                            </p>
                                            <p
                                                class="mt-1.5 text-4xl font-extrabold text-amber-700 dark:text-amber-300"
                                            >
                                                {{ pendingToday }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-gray-400"
                                            >
                                                docentes aún sin notificar
                                            </p>
                                        </div>
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 shadow-inner dark:bg-amber-800/40"
                                        >
                                            <XCircle
                                                class="h-5 w-5 text-amber-600 dark:text-amber-400"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ── LÍMITE DIARIO ── -->
                            <div
                                class="relative overflow-hidden rounded-2xl border p-6 shadow-sm"
                                :class="[
                                    gaugeColor.ring,
                                    gaugeColor.bg,
                                    gaugeColor.dark,
                                    'dark:border-gray-700',
                                ]"
                            >
                                <!-- Título y estado -->
                                <div
                                    class="mb-5 flex flex-wrap items-center justify-between gap-2"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-xl"
                                            :style="{
                                                backgroundColor:
                                                    gaugeColor.bar + '22',
                                            }"
                                        >
                                            <Users
                                                class="h-4.5 w-4.5"
                                                :style="{
                                                    color: gaugeColor.bar,
                                                }"
                                            />
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                            >
                                                Uso del límite diario
                                            </p>
                                            <p
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                Correos enviados hoy sobre el
                                                máximo permitido
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                            :class="[
                                                gaugeColor.text,
                                                gaugeColor.ring,
                                            ]"
                                        >
                                            {{ gaugeStatus }}
                                        </span>
                                        <span
                                            class="text-3xl font-extrabold"
                                            :style="{ color: gaugeColor.bar }"
                                        >
                                            {{ sentPercent }}%
                                        </span>
                                    </div>
                                </div>

                                <!-- Barra -->
                                <div
                                    class="relative h-5 w-full overflow-hidden rounded-full bg-white/60 shadow-inner ring-1 ring-gray-200 dark:bg-gray-800/60 dark:ring-gray-700"
                                >
                                    <div
                                        class="h-full rounded-full transition-all duration-700"
                                        :style="{
                                            width: `${sentPercent}%`,
                                            backgroundColor: gaugeColor.bar,
                                        }"
                                    />
                                    <!-- Marker 50% -->
                                    <div
                                        class="absolute top-0 bottom-0 w-px bg-gray-300/60 dark:bg-gray-600/60"
                                        style="left: 50%"
                                    />
                                    <!-- Marker 75% -->
                                    <div
                                        class="absolute top-0 bottom-0 w-px bg-gray-300/60 dark:bg-gray-600/60"
                                        style="left: 75%"
                                    />
                                </div>

                                <!-- Escala -->
                                <div
                                    class="mt-2 flex justify-between text-xs font-medium text-gray-400"
                                >
                                    <span>0</span>
                                    <span class="mr-[25%] ml-auto">75%</span>
                                    <span>{{ dailyLimit }}</span>
                                </div>

                                <!-- Detalle numérico -->
                                <div
                                    class="mt-4 grid grid-cols-3 divide-x divide-gray-200 rounded-xl bg-white/50 ring-1 ring-gray-100 dark:divide-gray-700 dark:bg-gray-800/40 dark:ring-gray-700"
                                >
                                    <div class="px-4 py-3 text-center">
                                        <p
                                            class="text-[11px] font-medium tracking-wide text-gray-400 uppercase"
                                        >
                                            Enviados
                                        </p>
                                        <p
                                            class="text-lg font-bold text-gray-800 dark:text-gray-100"
                                        >
                                            {{ sentToday }}
                                        </p>
                                    </div>
                                    <div class="px-4 py-3 text-center">
                                        <p
                                            class="text-[11px] font-medium tracking-wide text-gray-400 uppercase"
                                        >
                                            Restantes
                                        </p>
                                        <p
                                            class="text-lg font-bold"
                                            :style="{ color: gaugeColor.bar }"
                                        >
                                            {{ remaining }}
                                        </p>
                                    </div>
                                    <div class="px-4 py-3 text-center">
                                        <p
                                            class="text-[11px] font-medium tracking-wide text-gray-400 uppercase"
                                        >
                                            Límite
                                        </p>
                                        <p
                                            class="text-lg font-bold text-gray-800 dark:text-gray-100"
                                        >
                                            {{ dailyLimit }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- ══════════════════════════════════════
                     TAB 2: REPORTE CICLO
                ══════════════════════════════════════ -->
                <div v-else-if="activeTab === 'cycle'" class="p-6 space-y-5">

                    <!-- Sin periodo -->
                    <div
                        v-if="!hasPeriod"
                        class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 py-20 text-center dark:border-gray-700 dark:bg-gray-900/40"
                    >
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10">
                            <ClipboardList class="h-7 w-7 text-[#087ab1]/40" />
                        </div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            No hay un periodo académico activo.<br />
                            Selecciona uno desde el selector del encabezado.
                        </p>
                    </div>

                    <!-- ══ PANEL DE FILTROS ══ -->
                    <div
                        v-if="hasPeriod"
                        class="group relative overflow-hidden rounded-2xl border border-sky-100 bg-linear-to-br from-sky-50 to-white p-5 shadow-sm transition hover:shadow-md dark:border-sky-900/30 dark:from-sky-900/20 dark:to-gray-800"
                    >
                        <!-- Header -->
                        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-100 shadow-inner dark:bg-sky-800/40">
                                    <Filter class="h-5 w-5 text-[#087ab1] dark:text-sky-400" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium tracking-wide text-[#087ab1] uppercase dark:text-sky-400">
                                        Filtrar reporte
                                    </p>
                                    <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ cycleFilterLabel }}
                                    </p>
                                </div>
                            </div>

                            <!-- Badge + limpiar -->
                            <div v-if="activeCycleFiltersCount > 0" class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#087ab1] px-2.5 py-1 text-xs font-semibold text-white shadow-sm">
                                    <Filter class="h-3 w-3" />
                                    {{ activeCycleFiltersCount }} activo{{ activeCycleFiltersCount > 1 ? 's' : '' }}
                                </span>
                                <button
                                    type="button"
                                    @click="clearCycleFilters"
                                    class="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-white px-2.5 py-1 text-xs font-medium text-sky-700 shadow-sm transition hover:bg-sky-50 dark:border-sky-700 dark:bg-gray-800 dark:text-sky-300 dark:hover:bg-sky-900/30"
                                >
                                    <X class="h-3 w-3" />
                                    Limpiar
                                </button>
                            </div>
                        </div>

                        <!-- Selectores en cascada -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <!-- Campus -->
                            <div>
                                <label class="mb-1.5 flex items-center gap-1.5 text-xs font-medium tracking-wide text-[#087ab1] uppercase dark:text-sky-400">
                                    <Building2 class="h-3.5 w-3.5" />
                                    Campus
                                </label>
                                <div class="relative">
                                    <select
                                        v-model="cycleCampusId"
                                        @change="onCampusChange"
                                        class="w-full appearance-none rounded-xl border py-2.5 pl-4 pr-9 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#087ab1]/30"
                                        :class="cycleCampusId
                                            ? 'border-[#087ab1] bg-[#087ab1]/5 text-[#087ab1] dark:border-[#087ab1]/60 dark:bg-[#087ab1]/10 dark:text-sky-300'
                                            : 'border-sky-100 bg-white text-gray-700 hover:border-sky-200 dark:border-sky-800/50 dark:bg-gray-800 dark:text-gray-300'"
                                    >
                                        <option :value="null">Todos los campus</option>
                                        <option v-for="c in campusList" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                                        <svg class="h-4 w-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Facultad -->
                            <div>
                                <label class="mb-1.5 flex items-center gap-1.5 text-xs font-medium tracking-wide text-[#087ab1] uppercase dark:text-sky-400">
                                    <GraduationCap class="h-3.5 w-3.5" />
                                    Facultad
                                    <span v-if="filteredFacultyList.length > 0" class="ml-0.5 font-normal normal-case text-gray-400">({{ filteredFacultyList.length }})</span>
                                </label>
                                <div class="relative">
                                    <select
                                        v-model="cycleFacultyId"
                                        @change="onFacultyChange"
                                        :disabled="filteredFacultyList.length === 0"
                                        class="w-full appearance-none rounded-xl border py-2.5 pl-4 pr-9 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#087ab1]/30 disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="cycleFacultyId
                                            ? 'border-[#087ab1] bg-[#087ab1]/5 text-[#087ab1] dark:border-[#087ab1]/60 dark:bg-[#087ab1]/10 dark:text-sky-300'
                                            : 'border-sky-100 bg-white text-gray-700 hover:border-sky-200 dark:border-sky-800/50 dark:bg-gray-800 dark:text-gray-300'"
                                    >
                                        <option :value="null">Todas las facultades</option>
                                        <option v-for="f in filteredFacultyList" :key="f.id" :value="f.id">{{ f.name }}</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                                        <svg class="h-4 w-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Escuela profesional -->
                            <div>
                                <label class="mb-1.5 flex items-center gap-1.5 text-xs font-medium tracking-wide text-[#087ab1] uppercase dark:text-sky-400">
                                    <BookOpen class="h-3.5 w-3.5" />
                                    Escuela
                                    <span v-if="filteredProgramList.length > 0" class="ml-0.5 font-normal normal-case text-gray-400">({{ filteredProgramList.length }})</span>
                                </label>
                                <div class="relative">
                                    <select
                                        v-model="cycleProgramId"
                                        @change="applyCycleFilters"
                                        :disabled="filteredProgramList.length === 0"
                                        class="w-full appearance-none rounded-xl border py-2.5 pl-4 pr-9 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#087ab1]/30 disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="cycleProgramId
                                            ? 'border-[#087ab1] bg-[#087ab1]/5 text-[#087ab1] dark:border-[#087ab1]/60 dark:bg-[#087ab1]/10 dark:text-sky-300'
                                            : 'border-sky-100 bg-white text-gray-700 hover:border-sky-200 dark:border-sky-800/50 dark:bg-gray-800 dark:text-gray-300'"
                                    >
                                        <option :value="null">Todas las escuelas</option>
                                        <option v-for="p in filteredProgramList" :key="p.id" :value="p.id">{{ p.name }}</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                                        <svg class="h-4 w-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Breadcrumb de alcance -->
                        <div v-if="activeCycleFiltersCount > 0" class="mt-4 flex flex-wrap items-center gap-1.5 border-t border-sky-100 pt-4 text-xs dark:border-sky-900/30">
                            <span class="text-gray-400 dark:text-gray-500">Mostrando:</span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#087ab1]/10 px-2.5 py-0.5 font-semibold text-[#087ab1] dark:bg-[#087ab1]/20 dark:text-sky-300">
                                <Building2 class="h-3 w-3" />
                                {{ cycleCampusId ? campusList.find(c => c.id === cycleCampusId)?.name : 'Todos los campus' }}
                            </span>
                            <template v-if="cycleFacultyId">
                                <span class="text-sky-300 dark:text-sky-700">›</span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#087ab1]/10 px-2.5 py-0.5 font-semibold text-[#087ab1] dark:bg-[#087ab1]/20 dark:text-sky-300">
                                    <GraduationCap class="h-3 w-3" />
                                    {{ filteredFacultyList.find(f => f.id === cycleFacultyId)?.name ?? '—' }}
                                </span>
                            </template>
                            <template v-if="cycleProgramId">
                                <span class="text-sky-300 dark:text-sky-700">›</span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#087ab1]/10 px-2.5 py-0.5 font-semibold text-[#087ab1] dark:bg-[#087ab1]/20 dark:text-sky-300">
                                    <BookOpen class="h-3 w-3" />
                                    {{ filteredProgramList.find(p => p.id === cycleProgramId)?.name ?? '—' }}
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- ── Gráfica horizontal ── -->
                    <div
                        v-if="hasPeriod"
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="mb-4 flex items-center gap-3 border-b border-gray-200/60 pb-3 dark:border-gray-700/60">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                style="background: rgba(220,38,38,0.1)"
                            >
                                <Users class="h-4 w-4" style="color: #dc2626" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Top de docentes con más notificaciones</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Rúbros vencidos · Ciclo:
                                    <span class="font-medium text-[#087ab1]">{{ currentPeriodName }}</span>
                                </p>
                            </div>
                        </div>
                        <VueApexCharts
                            type="bar"
                            :height="Math.max(280, topTeachersCycle.length * 88)"
                            :options="cycleChartOptions"
                            :series="cycleChartSeries"
                        />
                    </div>

                    <!-- ── Campus + Facultad (grid adyacente) ── -->
                    <div
                        v-if="byCampusNotifications.length > 0 || byFacultyNotifications.length > 0"
                        class="grid grid-cols-2 gap-5"
                    >
                        <!-- Notificaciones por campus -->
                        <div
                            v-if="byCampusNotifications.length > 0"
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div class="mb-4 flex items-center gap-3 border-b border-gray-200/60 pb-3 dark:border-gray-700/60">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                    style="background: rgba(8,122,177,0.1)"
                                >
                                    <BarChart2 class="h-4 w-4" style="color: #087ab1" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Notificaciones por campus</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Enviadas · Ciclo:
                                        <span class="font-medium text-[#087ab1]">{{ currentPeriodName }}</span>
                                    </p>
                                </div>
                            </div>
                            <VueApexCharts
                                type="bar"
                                :height="Math.round(Math.max(280, topTeachersCycle.length * 88) / 2)"
                                :options="campusChartOptions"
                                :series="campusChartSeries"
                            />
                        </div>

                        <!-- Notificaciones por facultad -->
                        <div
                            v-if="byFacultyNotifications.length > 0"
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div class="mb-4 flex items-center gap-3 border-b border-gray-200/60 pb-3 dark:border-gray-700/60">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                    style="background: rgba(99,102,241,0.1)"
                                >
                                    <ClipboardList class="h-4 w-4" style="color: #6366f1" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Notificaciones por facultad</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Enviadas · Ciclo:
                                        <span class="font-medium text-[#087ab1]">{{ currentPeriodName }}</span>
                                    </p>
                                </div>
                            </div>
                            <VueApexCharts
                                type="bar"
                                :height="Math.round(Math.max(280, topTeachersCycle.length * 88) / 2)"
                                :options="facultyChartOptions"
                                :series="facultyChartSeries"
                            />
                        </div>
                    </div>

                    <!-- ── Notificaciones por programa ── -->
                    <div
                        v-if="byProgramNotifications.length > 0"
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="mb-4 flex items-center gap-3 border-b border-gray-200/60 pb-3 dark:border-gray-700/60">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                style="background: rgba(16,185,129,0.1)"
                            >
                                <CheckCircle2 class="h-4 w-4" style="color: #10b981" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Notificaciones por programa de estudio</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Enviadas · Ciclo:
                                    <span class="font-medium text-[#087ab1]">{{ currentPeriodName }}</span>
                                </p>
                            </div>
                        </div>
                        <VueApexCharts
                            type="bar"
                            :height="Math.max(200, byProgramNotifications.length * 52)"
                            :options="programChartOptions"
                            :series="programChartSeries"
                        />
                    </div>

                    <!-- ── Tabla detalle docentes ── -->
                    <div
                        v-if="teacherDetailReport.length > 0"
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                    >
                        <!-- Header -->
                        <div class="flex flex-wrap items-center gap-3 border-b border-gray-200/60 px-5 py-4 dark:border-gray-700/60">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                style="background: rgba(8,122,177,0.1)"
                            >
                                <Users class="h-4 w-4" style="color: #087ab1" />
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Detalle de docentes notificados</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ teacherDetailReport.length }} docentes · Ciclo:
                                    <span class="font-medium text-[#087ab1]">{{ currentPeriodName }}</span>
                                </p>
                            </div>
                            <!-- Botones exportar -->
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="downloadTeacherExcel"
                                    class="flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-800/40 dark:bg-emerald-900/20 dark:text-emerald-400"
                                >
                                    <FileSpreadsheet class="h-3.5 w-3.5" />
                                    Excel
                                </button>
                                <button
                                    type="button"
                                    @click="downloadTeacherPdf"
                                    class="flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-800/40 dark:bg-red-900/20 dark:text-red-400"
                                >
                                    <FileText class="h-3.5 w-3.5" />
                                    PDF
                                </button>
                            </div>
                        </div>

                        <!-- Tabla -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-800/60">
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Docente</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Campus</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Cursos</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-400">Rúbros vencidos</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-400">Notificaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(teacher, index) in tablePaginated"
                                        :key="index"
                                        class="transition hover:bg-gray-50/70 dark:hover:bg-gray-700/20"
                                        :class="index < tablePaginated.length - 1 ? 'border-b border-gray-100 dark:border-gray-700/40' : ''"
                                    >
                                        <!-- # -->
                                        <td class="px-4 py-3 text-xs text-gray-400">{{ tableFrom + index }}</td>

                                        <!-- Docente -->
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-gray-800 dark:text-gray-100">{{ teacher.name }}</p>
                                            <p class="text-xs text-gray-400">DNI: {{ teacher.dni }}</p>
                                        </td>

                                        <!-- Campus -->
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                <span
                                                    v-for="campus in teacher.campuses"
                                                    :key="campus"
                                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                                                    :style="{ backgroundColor: campusColorMap[campus] ?? '#6b7280' }"
                                                >
                                                    {{ campus }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Cursos -->
                                        <td class="px-4 py-3">
                                            <div v-if="teacher.courses.length > 0" class="space-y-1">
                                                <div
                                                    v-for="(course, ci) in teacher.courses"
                                                    :key="ci"
                                                    class="flex items-start gap-1.5 text-xs"
                                                >
                                                    <span
                                                        class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                                        :style="{ backgroundColor: campusColorMap[course.campus] ?? '#6b7280' }"
                                                    ></span>
                                                    <span>
                                                        <span class="font-medium text-gray-700 dark:text-gray-200">{{ course.name }}</span>
                                                        <span class="ml-1 text-gray-400">· Ciclo {{ course.cycle }} · {{ course.group }}</span>
                                                        <span
                                                            class="ml-1.5 inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                                            :class="course.expired > 0
                                                                ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                                                                : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500'"
                                                        >{{ course.expired }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                            <span v-else class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                        </td>

                                        <!-- Rúbros vencidos -->
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="inline-flex items-center justify-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                                :class="teacher.total_expired > 0
                                                    ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                                    : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500'"
                                            >
                                                {{ teacher.total_expired }}
                                            </span>
                                        </td>

                                        <!-- Total notificaciones -->
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center justify-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-[#087ab1] dark:bg-blue-900/20 dark:text-blue-300">
                                                {{ teacher.total_sent }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div
                            v-if="tableTotalPages > 1"
                            class="flex items-center justify-between border-t border-gray-100 px-5 py-3 dark:border-gray-700/60"
                        >
                            <p class="text-xs text-gray-400">
                                Mostrando <span class="font-semibold text-gray-600 dark:text-gray-300">{{ tableFrom }}–{{ tableTo }}</span>
                                de <span class="font-semibold text-gray-600 dark:text-gray-300">{{ teacherDetailReport.length }}</span> docentes
                            </p>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    :disabled="tablePage === 1"
                                    @click="tablePage--"
                                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700/40"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </button>
                                <button
                                    v-for="p in tableTotalPages"
                                    :key="p"
                                    type="button"
                                    @click="tablePage = p"
                                    class="flex h-7 w-7 items-center justify-center rounded-lg border text-xs font-semibold transition"
                                    :class="p === tablePage
                                        ? 'border-[#087ab1] bg-[#087ab1] text-white'
                                        : 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700/40'"
                                >
                                    {{ p }}
                                </button>
                                <button
                                    type="button"
                                    :disabled="tablePage === tableTotalPages"
                                    @click="tablePage++"
                                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700/40"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
