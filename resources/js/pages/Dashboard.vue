<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    BarChart3,
    Bell,
    CalendarDays,
    CheckCircle2,
    Eye,
    EyeOff,
    Mail,
    Send,
    Settings2,
    TrendingUp,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import type { AppPageProps } from '@/types';

/* ── Breadcrumbs ── */
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }];

/* ── Usuario actual ── */
const page = usePage<AppPageProps>();
const userName = computed(() => page.props.auth?.user?.name ?? '');
const firstName = computed(() => userName.value.split(' ')[0]);

/* ── Props ── */
interface Period { id: number; name: string; status: string }
interface Kpis {
    total_batches: number; sent: number; failed: number; skipped: number;
    pending: number; total_details: number; success_rate: number;
    total_teachers: number; notified_teachers: number;
}
interface Charts {
    donut: { series: number[]; labels: string[] }
    batches_by_status: { categories: string[]; data: number[] }
    trend: { dates: string[]; values: number[] }
    top_expired: { name: string; expired: number }[]
    by_campus: { categories: string[]; sent: number[]; failed: number[] }
    eval_by_campus: { categories: string[]; evaluated: number[]; expired: number[] }
}

const props = defineProps<{ period: Period | null; kpis: Kpis; charts: Charts }>()

/* ── Panel de visibilidad configurable ── */
const showSettings = ref(false)
const visibility = ref({
    kpis:           true,
    donut:          true,
    batchStatus:    true,
    trend:          true,
    topExpired:     true,
    byCampus:       true,
    evalByCampus:   true,
})

const sections = [
    { key: 'kpis',         label: 'Tarjetas KPI' },
    { key: 'donut',        label: 'Distribución de notificaciones' },
    { key: 'batchStatus',  label: 'Lotes por estado' },
    { key: 'trend',        label: 'Tendencia de envíos' },
    { key: 'topExpired',   label: 'Top docentes vencidos' },
    { key: 'byCampus',     label: 'Notificaciones por campus' },
    { key: 'evalByCampus', label: 'Evaluaciones por campus' },
]

/* ── Helpers gráficos ── */
const isDark = ref(document.documentElement.classList.contains('dark'))
const chartFontFamily = "'Inter', 'ui-sans-serif', system-ui, sans-serif"
const chartForeColor  = computed(() => isDark.value ? '#94a3b8' : '#64748b')

const baseChart = computed(() => ({
    fontFamily: chartFontFamily,
    foreColor:  chartForeColor.value,
    toolbar:    { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } },
    animations: { enabled: true, speed: 600, animateGradually: { enabled: true, delay: 80 } },
}))

/* ── Paleta de marca ── */
const NAVY  = '#04395a'
const BLUE  = '#68c8fb'
const GREEN = '#10b981'
const RED   = '#ef4444'
const AMBER = '#f59e0b'
const SLATE = '#94a3b8'

/* ════════════════════════════════════════
   DONUT — Distribución por estado
════════════════════════════════════════ */
const donutOptions = computed(() => ({
    chart: { ...baseChart.value, type: 'donut' },
    labels: props.charts.donut.labels,
    colors: [GREEN, RED, AMBER, SLATE],
    legend: { position: 'bottom', fontSize: '13px' },
    dataLabels: { enabled: true, formatter: (val: number) => `${val.toFixed(1)}%` },
    plotOptions: {
        pie: { donut: { size: '65%', labels: { show: true, total: {
            show: true, label: 'Total', fontSize: '14px', fontWeight: 600,
            formatter: () => props.kpis.total_details.toLocaleString()
        }}}}
    },
    stroke: { width: 2, colors: [isDark.value ? '#1e293b' : '#ffffff'] },
    tooltip: { y: { formatter: (v: number) => `${v.toLocaleString()} notificaciones` } },
}))

/* ════════════════════════════════════════
   BARRAS — Lotes por estado
════════════════════════════════════════ */
const batchBarOptions = computed(() => ({
    chart: { ...baseChart.value, type: 'bar', toolbar: { show: false } },
    colors: [SLATE, BLUE, '#3b82f6', GREEN, AMBER, RED],
    plotOptions: { bar: { borderRadius: 6, columnWidth: '55%', distributed: true } },
    dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 600 } },
    legend: { show: false },
    xaxis: {
        categories: props.charts.batches_by_status.categories,
        labels: { style: { fontSize: '11px' }, rotate: -20 },
        axisBorder: { show: false }, axisTicks: { show: false },
    },
    yaxis: { labels: { formatter: (v: number) => Math.round(v).toString() } },
    grid: { borderColor: isDark.value ? '#1e293b' : '#f1f5f9', strokeDashArray: 4 },
    tooltip: { y: { formatter: (v: number) => `${v} lote${v !== 1 ? 's' : ''}` } },
}))
const batchBarSeries = computed(() => [{ name: 'Lotes', data: props.charts.batches_by_status.data }])

/* ════════════════════════════════════════
   ÁREA — Tendencia de envíos
════════════════════════════════════════ */
const trendOptions = computed(() => ({
    chart: { ...baseChart.value, type: 'area', id: 'trend' },
    colors: [BLUE],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 100] } },
    stroke: { curve: 'smooth', width: 2.5 },
    dataLabels: { enabled: false },
    xaxis: {
        type: 'datetime',
        categories: props.charts.trend.dates,
        labels: { datetimeFormatter: { year: 'yyyy', month: "MMM 'yy", day: 'dd MMM' } },
        axisBorder: { show: false }, axisTicks: { show: false },
    },
    yaxis: { labels: { formatter: (v: number) => Math.round(v).toString() }, min: 0 },
    grid: { borderColor: isDark.value ? '#1e293b' : '#f1f5f9', strokeDashArray: 4 },
    tooltip: { x: { format: 'dd MMM yyyy' }, y: { formatter: (v: number) => `${v} enviados` } },
    markers: { size: props.charts.trend.dates.length < 15 ? 4 : 0, colors: [BLUE], strokeWidth: 2, strokeColors: '#fff' },
}))
const trendSeries = computed(() => [{ name: 'Enviados', data: props.charts.trend.values }])

/* ════════════════════════════════════════
   BARRAS HORIZ — Top docentes vencidos
════════════════════════════════════════ */
const topExpiredOptions = computed(() => ({
    chart: { ...baseChart.value, type: 'bar', toolbar: { show: false } },
    colors: [RED],
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%', dataLabels: { position: 'top' } } },
    dataLabels: { enabled: true, offsetX: 4, style: { fontSize: '11px', colors: [isDark.value ? '#cbd5e1' : '#334155'] } },
    xaxis: {
        categories: props.charts.top_expired.map(t => t.name.split(' ').slice(0, 3).join(' ')),
        labels: { style: { fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false },
    },
    yaxis: { labels: { style: { fontSize: '11px' }, maxWidth: 160 } },
    grid: { borderColor: isDark.value ? '#1e293b' : '#f1f5f9', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
    tooltip: { y: { formatter: (v: number) => `${v} componente${v !== 1 ? 's' : ''} vencido${v !== 1 ? 's' : ''}` } },
}))
const topExpiredSeries = computed(() => [{ name: 'Vencidos', data: props.charts.top_expired.map(t => t.expired) }])

/* ════════════════════════════════════════
   BARRAS AGRUPADAS — Por campus (notifs)
════════════════════════════════════════ */
const byCampusOptions = computed(() => ({
    chart: { ...baseChart.value, type: 'bar', toolbar: { show: false } },
    colors: [GREEN, RED],
    plotOptions: { bar: { borderRadius: 4, columnWidth: '60%', grouped: true } },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts.by_campus.categories,
        labels: { style: { fontSize: '11px' }, rotate: -20 },
        axisBorder: { show: false }, axisTicks: { show: false },
    },
    yaxis: { labels: { formatter: (v: number) => Math.round(v).toString() } },
    legend: { position: 'top', fontSize: '12px' },
    grid: { borderColor: isDark.value ? '#1e293b' : '#f1f5f9', strokeDashArray: 4 },
    tooltip: { y: { formatter: (v: number) => `${v.toLocaleString()}` } },
}))
const byCampusSeries = computed(() => [
    { name: 'Enviados', data: props.charts.by_campus.sent },
    { name: 'Fallidos', data: props.charts.by_campus.failed },
])

/* ════════════════════════════════════════
   BARRAS APILADAS — Evaluaciones por campus
════════════════════════════════════════ */
const evalByCampusOptions = computed(() => ({
    chart: { ...baseChart.value, type: 'bar', stacked: true, toolbar: { show: false } },
    colors: [BLUE, RED],
    plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts.eval_by_campus.categories,
        labels: { style: { fontSize: '11px' }, rotate: -20 },
        axisBorder: { show: false }, axisTicks: { show: false },
    },
    yaxis: { labels: { formatter: (v: number) => Math.round(v).toString() } },
    legend: { position: 'top', fontSize: '12px' },
    grid: { borderColor: isDark.value ? '#1e293b' : '#f1f5f9', strokeDashArray: 4 },
    tooltip: { y: { formatter: (v: number) => `${v.toLocaleString()} componentes` } },
    fill: { opacity: 1 },
}))
const evalByCampusSeries = computed(() => [
    { name: 'Evaluados', data: props.charts.eval_by_campus.evaluated },
    { name: 'Vencidos',  data: props.charts.eval_by_campus.expired   },
])

/* ── KPI cards ── */
const kpiCards = computed(() => [
    {
        label: 'Lotes de notificación',
        value: props.kpis.total_batches,
        icon: Bell,
        iconColor: '#68c8fb',
        iconBg: 'rgba(104,200,251,0.15)',
        border: 'rgba(104,200,251,0.3)',
        sub: `${props.kpis.total_details.toLocaleString()} notificaciones totales`,
        accent: false,
    },
    {
        label: 'Enviados exitosos',
        value: props.kpis.sent.toLocaleString(),
        icon: CheckCircle2,
        iconColor: '#10b981',
        iconBg: 'rgba(16,185,129,0.12)',
        border: 'rgba(16,185,129,0.25)',
        sub: `Tasa de éxito: ${props.kpis.success_rate}%`,
        rate: props.kpis.success_rate,
        accent: props.kpis.success_rate >= 90,
    },
    {
        label: 'Fallidos / sin correo',
        value: props.kpis.failed.toLocaleString(),
        icon: XCircle,
        iconColor: '#ef4444',
        iconBg: 'rgba(239,68,68,0.1)',
        border: 'rgba(239,68,68,0.25)',
        sub: `Sin correo: ${props.kpis.skipped.toLocaleString()}`,
        alert: props.kpis.failed > 0,
        accent: false,
    },
    {
        label: 'Docentes notificados',
        value: props.kpis.notified_teachers.toLocaleString(),
        icon: Users,
        iconColor: '#68c8fb',
        iconBg: 'rgba(104,200,251,0.12)',
        border: 'rgba(104,200,251,0.2)',
        sub: `de ${props.kpis.total_teachers.toLocaleString()} docentes`,
        accent: false,
    },
    {
        label: 'Pendientes',
        value: props.kpis.pending.toLocaleString(),
        icon: Send,
        iconColor: '#f59e0b',
        iconBg: 'rgba(245,158,11,0.1)',
        border: 'rgba(245,158,11,0.2)',
        sub: 'En cola o sin procesar',
        accent: false,
    },
])

const hasNoData = computed(() => props.kpis.total_details === 0)

/* ── Hora de saludo ── */
const greeting = computed(() => {
    const h = new Date().getHours()
    if (h < 12) return 'Buenos días'
    if (h < 18) return 'Buenas tardes'
    return 'Buenas noches'
})
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/60 p-6 dark:bg-background">
            <div class="mx-auto max-w-7xl space-y-6">

                <!-- ══════════════════════════════════════
                     HERO — Bienvenida + Periodo activo
                ═══════════════════════════════════════ -->
                <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg"
                     style="background: linear-gradient(135deg, #032f4a 0%, #04395a 50%, #065c8e 100%);">

                    <!-- Fondo decorativo -->
                    <div class="pointer-events-none absolute inset-0">
                        <div class="absolute -top-16 -right-16 h-56 w-56 rounded-full opacity-10"
                             style="background: radial-gradient(circle, #68c8fb, transparent);" />
                        <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full opacity-10"
                             style="background: radial-gradient(circle, #68c8fb, transparent);" />
                        <div class="absolute inset-0 opacity-[0.025]"
                             style="background-image: radial-gradient(circle, #68c8fb 1px, transparent 1px); background-size: 28px 28px;" />
                    </div>

                    <div class="relative flex flex-wrap items-center justify-between gap-4">
                        <!-- Saludo -->
                        <div>
                            <p class="text-sm font-medium" style="color: rgba(104,200,251,0.8);">
                                {{ greeting }},
                            </p>
                            <h1 class="text-2xl font-bold text-white">{{ firstName }} 👋</h1>
                            <p class="mt-1 text-sm" style="color: rgba(255,255,255,0.55);">
                                Aquí tienes el resumen de notificaciones del periodo activo.
                            </p>
                        </div>

                        <!-- Periodo + configurar -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Badge periodo -->
                            <div class="flex items-center gap-2 rounded-xl px-4 py-2.5"
                                 style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);">
                                <CalendarDays class="h-4 w-4" style="color: #68c8fb;" />
                                <div>
                                    <p class="text-[10px] font-medium uppercase tracking-wider" style="color: rgba(255,255,255,0.5);">Periodo activo</p>
                                    <div class="flex items-center gap-1.5">
                                        <span class="relative flex h-1.5 w-1.5">
                                            <span v-if="period?.status === 'active'"
                                                  class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                                                  style="background: #68c8fb;" />
                                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full"
                                                  :style="period?.status === 'active' ? 'background: #68c8fb' : 'background: #94a3b8'" />
                                        </span>
                                        <span class="text-sm font-semibold text-white">
                                            {{ period?.name ?? 'Sin periodo' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Botón configurar -->
                            <button
                                @click="showSettings = !showSettings"
                                class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition-all"
                                style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); color: rgba(255,255,255,0.7);"
                                :style="showSettings ? 'background: rgba(104,200,251,0.2); border-color: rgba(104,200,251,0.4); color: #68c8fb;' : ''"
                            >
                                <Settings2 class="h-4 w-4" />
                                Vistas
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── PANEL CONFIGURACIÓN ── -->
                <Transition name="settings-panel">
                    <div v-if="showSettings" class="rounded-2xl border border-border bg-background p-5 shadow-sm">
                        <p class="mb-3 text-sm font-semibold text-foreground">Mostrar / ocultar secciones</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="s in sections"
                                :key="s.key"
                                @click="(visibility as any)[s.key] = !(visibility as any)[s.key]"
                                class="flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
                                :class="(visibility as any)[s.key]
                                    ? 'border-[#68c8fb]/40 bg-[#04395a]/5 text-[#04395a] dark:text-[#68c8fb]'
                                    : 'border-border bg-muted text-muted-foreground'"
                            >
                                <Eye v-if="(visibility as any)[s.key]" class="h-3 w-3" />
                                <EyeOff v-else class="h-3 w-3" />
                                {{ s.label }}
                            </button>
                        </div>
                    </div>
                </Transition>

                <!-- ── SIN DATOS ── -->
                <div v-if="hasNoData"
                     class="flex flex-col items-center gap-4 rounded-2xl border border-dashed border-border bg-background py-20 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl"
                         style="background: rgba(4,57,90,0.06);">
                        <BarChart3 class="h-8 w-8" style="color: #04395a; opacity: 0.4;" />
                    </div>
                    <div>
                        <p class="text-base font-semibold text-foreground">Sin datos para este periodo</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Aún no se han enviado notificaciones en
                            <strong>{{ period?.name ?? 'el periodo seleccionado' }}</strong>.
                        </p>
                    </div>
                </div>

                <template v-else>

                    <!-- ══════════════════════════════════
                         KPI CARDS
                    ═══════════════════════════════════ -->
                    <Transition name="section-fade">
                        <div v-if="visibility.kpis" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                            <div
                                v-for="card in kpiCards"
                                :key="card.label"
                                class="relative overflow-hidden rounded-2xl border bg-background p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                                :style="`border-color: ${card.border}`"
                            >
                                <!-- Acento de fondo sutil -->
                                <div class="pointer-events-none absolute inset-0 opacity-30"
                                     :style="`background: radial-gradient(ellipse at top right, ${card.iconBg}, transparent 70%);`" />

                                <div class="relative flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-medium text-muted-foreground">{{ card.label }}</p>
                                        <p class="mt-1.5 text-2xl font-bold tracking-tight text-foreground">{{ card.value }}</p>
                                        <p class="mt-1 truncate text-[11px] text-muted-foreground/70">{{ card.sub }}</p>
                                    </div>
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                         :style="`background: ${card.iconBg}; border: 1px solid ${card.border};`">
                                        <component :is="card.icon" class="h-5 w-5" :style="`color: ${card.iconColor}`" />
                                    </div>
                                </div>

                                <!-- Success rate bar -->
                                <div v-if="'rate' in card" class="relative mt-3">
                                    <div class="h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div
                                            class="h-full rounded-full transition-all duration-700"
                                            :class="(card.rate ?? 0) >= 90 ? 'bg-emerald-500' : (card.rate ?? 0) >= 70 ? 'bg-amber-500' : 'bg-red-500'"
                                            :style="{ width: `${card.rate}%` }"
                                        />
                                    </div>
                                </div>

                                <!-- Alert dot -->
                                <span v-if="card.alert" class="absolute right-3 top-3 flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75" />
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500" />
                                </span>
                            </div>
                        </div>
                    </Transition>

                    <!-- ══════════════════════════════════
                         FILA 1: Donut + Barras estado
                    ═══════════════════════════════════ -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                        <Transition name="section-fade">
                            <div v-if="visibility.donut" class="rounded-2xl border border-border bg-background p-5 shadow-sm">
                                <div class="mb-4 flex items-center gap-3 border-b border-border/60 pb-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg"
                                         style="background: rgba(16,185,129,0.1);">
                                        <Mail class="h-4 w-4" style="color: #10b981;" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-foreground">Distribución de notificaciones</h3>
                                        <p class="text-xs text-muted-foreground">Por estado de envío</p>
                                    </div>
                                </div>
                                <VueApexCharts type="donut" height="280" :options="donutOptions" :series="charts.donut.series" />
                            </div>
                        </Transition>

                        <Transition name="section-fade">
                            <div v-if="visibility.batchStatus" class="rounded-2xl border border-border bg-background p-5 shadow-sm">
                                <div class="mb-4 flex items-center gap-3 border-b border-border/60 pb-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg"
                                         style="background: rgba(104,200,251,0.12);">
                                        <Bell class="h-4 w-4" style="color: #68c8fb;" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-foreground">Lotes por estado</h3>
                                        <p class="text-xs text-muted-foreground">Distribución de lotes de notificación</p>
                                    </div>
                                </div>
                                <VueApexCharts type="bar" height="280" :options="batchBarOptions" :series="batchBarSeries" />
                            </div>
                        </Transition>
                    </div>

                    <!-- ══════════════════════════════════
                         FILA 2: Tendencia (full width)
                    ═══════════════════════════════════ -->
                    <Transition name="section-fade">
                        <div v-if="visibility.trend" class="rounded-2xl border border-border bg-background p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-3 border-b border-border/60 pb-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg"
                                     style="background: rgba(104,200,251,0.12);">
                                    <TrendingUp class="h-4 w-4" style="color: #68c8fb;" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-foreground">Tendencia de envíos</h3>
                                    <p class="text-xs text-muted-foreground">Notificaciones enviadas exitosamente por día</p>
                                </div>
                            </div>
                            <div v-if="charts.trend.dates.length === 0"
                                 class="flex flex-col items-center gap-2 py-12 text-center">
                                <TrendingUp class="h-10 w-10 text-muted-foreground/25" />
                                <p class="text-sm text-muted-foreground">Sin datos de tendencia temporal aún</p>
                            </div>
                            <VueApexCharts v-else type="area" height="240" :options="trendOptions" :series="trendSeries" />
                        </div>
                    </Transition>

                    <!-- ══════════════════════════════════
                         FILA 3: Top vencidos + Por campus
                    ═══════════════════════════════════ -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                        <Transition name="section-fade">
                            <div v-if="visibility.topExpired" class="rounded-2xl border border-border bg-background p-5 shadow-sm">
                                <div class="mb-4 flex items-center gap-3 border-b border-border/60 pb-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg"
                                         style="background: rgba(239,68,68,0.1);">
                                        <AlertTriangle class="h-4 w-4" style="color: #ef4444;" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-foreground">Top docentes con vencidos</h3>
                                        <p class="text-xs text-muted-foreground">Componentes vencidos acumulados en el periodo</p>
                                    </div>
                                </div>
                                <div v-if="charts.top_expired.length === 0"
                                     class="flex flex-col items-center gap-2 py-10 text-center">
                                    <CheckCircle2 class="h-10 w-10 text-emerald-400/40" />
                                    <p class="text-sm text-muted-foreground">¡Sin vencidos en este periodo!</p>
                                </div>
                                <VueApexCharts
                                    v-else type="bar"
                                    :height="Math.max(220, charts.top_expired.length * 42)"
                                    :options="topExpiredOptions"
                                    :series="topExpiredSeries"
                                />
                            </div>
                        </Transition>

                        <Transition name="section-fade">
                            <div v-if="visibility.byCampus" class="rounded-2xl border border-border bg-background p-5 shadow-sm">
                                <div class="mb-4 flex items-center gap-3 border-b border-border/60 pb-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg"
                                         style="background: rgba(16,185,129,0.1);">
                                        <Send class="h-4 w-4" style="color: #10b981;" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-foreground">Notificaciones por campus</h3>
                                        <p class="text-xs text-muted-foreground">Enviados vs. fallidos por sede</p>
                                    </div>
                                </div>
                                <div v-if="charts.by_campus.categories.length === 0"
                                     class="flex flex-col items-center gap-2 py-10 text-center">
                                    <BarChart3 class="h-10 w-10 text-muted-foreground/25" />
                                    <p class="text-sm text-muted-foreground">Sin datos por campus</p>
                                </div>
                                <VueApexCharts v-else type="bar" height="280" :options="byCampusOptions" :series="byCampusSeries" />
                            </div>
                        </Transition>
                    </div>

                    <!-- ══════════════════════════════════
                         FILA 4: Eval por campus (full)
                    ═══════════════════════════════════ -->
                    <Transition name="section-fade">
                        <div v-if="visibility.evalByCampus" class="rounded-2xl border border-border bg-background p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-3 border-b border-border/60 pb-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg"
                                     style="background: rgba(104,200,251,0.12);">
                                    <BarChart3 class="h-4 w-4" style="color: #68c8fb;" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-foreground">Evaluaciones por campus</h3>
                                    <p class="text-xs text-muted-foreground">Componentes evaluados vs. vencidos por sede académica</p>
                                </div>
                            </div>
                            <div v-if="charts.eval_by_campus.categories.length === 0"
                                 class="flex flex-col items-center gap-2 py-10 text-center">
                                <BarChart3 class="h-10 w-10 text-muted-foreground/25" />
                                <p class="text-sm text-muted-foreground">Sin datos de evaluación por campus</p>
                            </div>
                            <VueApexCharts v-else type="bar" height="260" :options="evalByCampusOptions" :series="evalByCampusSeries" />
                        </div>
                    </Transition>

                </template>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.settings-panel-enter-active,
.settings-panel-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.settings-panel-enter-from,
.settings-panel-leave-to    { opacity: 0; transform: translateY(-8px); }

.section-fade-enter-active,
.section-fade-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.section-fade-enter-from,
.section-fade-leave-to     { opacity: 0; transform: translateY(6px); }
</style>
