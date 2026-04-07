<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
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

/* ── Breadcrumbs ── */
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }];

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

/* ── Helpers ── */
const isDark = ref(document.documentElement.classList.contains('dark'))
const chartFontFamily = "'Inter', 'ui-sans-serif', system-ui, sans-serif"
const chartForeColor  = computed(() => isDark.value ? '#94a3b8' : '#64748b')

const baseChart = computed(() => ({
    fontFamily: chartFontFamily,
    foreColor:  chartForeColor.value,
    toolbar:    { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } },
    animations: { enabled: true, speed: 600, animateGradually: { enabled: true, delay: 80 } },
}))

/* ════════════════════════════════════════
   DONUT — Distribución por estado
════════════════════════════════════════ */
const donutOptions = computed(() => ({
    chart: { ...baseChart.value, type: 'donut' },
    labels: props.charts.donut.labels,
    colors: ['#10b981', '#ef4444', '#f59e0b', '#94a3b8'],
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
const batchStatusColors = ['#94a3b8', '#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ef4444']
const batchBarOptions = computed(() => ({
    chart: { ...baseChart.value, type: 'bar', toolbar: { show: false } },
    colors: batchStatusColors,
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
    colors: ['#6366f1'],
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
    markers: { size: props.charts.trend.dates.length < 15 ? 4 : 0, colors: ['#6366f1'], strokeWidth: 2, strokeColors: '#fff' },
}))
const trendSeries = computed(() => [{ name: 'Enviados', data: props.charts.trend.values }])

/* ════════════════════════════════════════
   BARRAS HORIZ — Top docentes vencidos
════════════════════════════════════════ */
const topExpiredOptions = computed(() => ({
    chart: { ...baseChart.value, type: 'bar', toolbar: { show: false } },
    colors: ['#ef4444'],
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
    colors: ['#10b981', '#ef4444'],
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
    colors: ['#6366f1', '#f43f5e'],
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

/* ── KPI config ── */
const kpiCards = computed(() => [
    {
        label: 'Lotes enviados',
        value: props.kpis.total_batches,
        icon: Bell,
        color: 'text-indigo-600 dark:text-indigo-400',
        bg: 'bg-indigo-100 dark:bg-indigo-900/40',
        sub: `${props.kpis.total_details.toLocaleString()} notificaciones totales`,
    },
    {
        label: 'Enviados exitosos',
        value: props.kpis.sent.toLocaleString(),
        icon: CheckCircle2,
        color: 'text-emerald-600 dark:text-emerald-400',
        bg: 'bg-emerald-100 dark:bg-emerald-900/40',
        sub: `Tasa de éxito: ${props.kpis.success_rate}%`,
        highlight: props.kpis.success_rate >= 90,
    },
    {
        label: 'Fallidos',
        value: props.kpis.failed.toLocaleString(),
        icon: XCircle,
        color: 'text-red-600 dark:text-red-400',
        bg: 'bg-red-100 dark:bg-red-900/40',
        sub: `Sin correo: ${props.kpis.skipped.toLocaleString()}`,
        alert: props.kpis.failed > 0,
    },
    {
        label: 'Docentes notificados',
        value: props.kpis.notified_teachers.toLocaleString(),
        icon: Users,
        color: 'text-violet-600 dark:text-violet-400',
        bg: 'bg-violet-100 dark:bg-violet-900/40',
        sub: `de ${props.kpis.total_teachers.toLocaleString()} docentes`,
    },
    {
        label: 'Pendientes',
        value: props.kpis.pending.toLocaleString(),
        icon: Send,
        color: 'text-amber-600 dark:text-amber-400',
        bg: 'bg-amber-100 dark:bg-amber-900/40',
        sub: 'En cola o sin procesar',
    },
])

const hasNoData = computed(() => props.kpis.total_details === 0)
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/50 p-6 dark:bg-background">
            <div class="mx-auto max-w-7xl space-y-6">

                <!-- ── HEADER ── -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-bold text-foreground">Reportes y Estadísticas</h1>
                        <div class="mt-1 flex items-center gap-2">
                            <CalendarDays class="h-3.5 w-3.5 text-muted-foreground" />
                            <span class="text-sm text-muted-foreground">Periodo:</span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span v-if="period?.status === 'active'" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75" />
                                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full" :class="period?.status === 'active' ? 'bg-emerald-500' : 'bg-indigo-400'" />
                                </span>
                                {{ period?.name ?? 'Sin periodo seleccionado' }}
                            </span>
                        </div>
                    </div>

                    <!-- Botón configurar -->
                    <button
                        @click="showSettings = !showSettings"
                        class="flex items-center gap-2 rounded-xl border border-border px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted"
                        :class="showSettings && 'bg-muted text-foreground'"
                    >
                        <Settings2 class="h-4 w-4" />
                        Configurar vistas
                    </button>
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
                                    ? 'border-indigo-300 bg-indigo-50 text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'
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
                <div v-if="hasNoData" class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-border bg-background py-16 text-center">
                    <BarChart3 class="h-14 w-14 text-muted-foreground/25" />
                    <p class="text-base font-semibold text-muted-foreground">Sin datos para este periodo</p>
                    <p class="text-sm text-muted-foreground/60">Aún no se han enviado notificaciones en <strong>{{ period?.name ?? 'el periodo seleccionado' }}</strong>.</p>
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
                                class="relative overflow-hidden rounded-2xl border bg-background p-5 shadow-sm transition-shadow hover:shadow-md"
                                :class="card.alert ? 'border-red-200 dark:border-red-800' : 'border-border'"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-muted-foreground">{{ card.label }}</p>
                                        <p class="mt-1 text-2xl font-bold tracking-tight text-foreground">{{ card.value }}</p>
                                        <p class="mt-1 text-[11px] text-muted-foreground/70 truncate">{{ card.sub }}</p>
                                    </div>
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :class="card.bg">
                                        <component :is="card.icon" class="h-5 w-5" :class="card.color" />
                                    </div>
                                </div>
                                <!-- Success rate bar -->
                                <div v-if="card.label === 'Enviados exitosos'" class="mt-3">
                                    <div class="h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div
                                            class="h-full rounded-full transition-all duration-700"
                                            :class="kpis.success_rate >= 90 ? 'bg-emerald-500' : kpis.success_rate >= 70 ? 'bg-amber-500' : 'bg-red-500'"
                                            :style="{ width: `${kpis.success_rate}%` }"
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
                                    <Mail class="h-4 w-4 text-indigo-500" />
                                    <div>
                                        <h3 class="text-sm font-semibold text-foreground">Distribución de notificaciones</h3>
                                        <p class="text-xs text-muted-foreground">Por estado de envío</p>
                                    </div>
                                </div>
                                <VueApexCharts
                                    type="donut"
                                    height="280"
                                    :options="donutOptions"
                                    :series="charts.donut.series"
                                />
                            </div>
                        </Transition>

                        <Transition name="section-fade">
                            <div v-if="visibility.batchStatus" class="rounded-2xl border border-border bg-background p-5 shadow-sm">
                                <div class="mb-4 flex items-center gap-3 border-b border-border/60 pb-3">
                                    <Bell class="h-4 w-4 text-violet-500" />
                                    <div>
                                        <h3 class="text-sm font-semibold text-foreground">Lotes por estado</h3>
                                        <p class="text-xs text-muted-foreground">Distribución de lotes de notificación</p>
                                    </div>
                                </div>
                                <VueApexCharts
                                    type="bar"
                                    height="280"
                                    :options="batchBarOptions"
                                    :series="batchBarSeries"
                                />
                            </div>
                        </Transition>
                    </div>

                    <!-- ══════════════════════════════════
                         FILA 2: Tendencia (full width)
                    ═══════════════════════════════════ -->
                    <Transition name="section-fade">
                        <div v-if="visibility.trend" class="rounded-2xl border border-border bg-background p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-3 border-b border-border/60 pb-3">
                                <TrendingUp class="h-4 w-4 text-indigo-500" />
                                <div>
                                    <h3 class="text-sm font-semibold text-foreground">Tendencia de envíos</h3>
                                    <p class="text-xs text-muted-foreground">Notificaciones enviadas exitosamente por día</p>
                                </div>
                            </div>
                            <div v-if="charts.trend.dates.length === 0" class="flex flex-col items-center gap-2 py-12 text-center">
                                <TrendingUp class="h-10 w-10 text-muted-foreground/25" />
                                <p class="text-sm text-muted-foreground">Sin datos de tendencia temporal aún</p>
                            </div>
                            <VueApexCharts
                                v-else
                                type="area"
                                height="240"
                                :options="trendOptions"
                                :series="trendSeries"
                            />
                        </div>
                    </Transition>

                    <!-- ══════════════════════════════════
                         FILA 3: Top vencidos + Por campus
                    ═══════════════════════════════════ -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                        <Transition name="section-fade">
                            <div v-if="visibility.topExpired" class="rounded-2xl border border-border bg-background p-5 shadow-sm">
                                <div class="mb-4 flex items-center gap-3 border-b border-border/60 pb-3">
                                    <AlertTriangle class="h-4 w-4 text-red-500" />
                                    <div>
                                        <h3 class="text-sm font-semibold text-foreground">Top docentes con vencidos</h3>
                                        <p class="text-xs text-muted-foreground">Componentes vencidos acumulados en el periodo</p>
                                    </div>
                                </div>
                                <div v-if="charts.top_expired.length === 0" class="flex flex-col items-center gap-2 py-10 text-center">
                                    <CheckCircle2 class="h-10 w-10 text-emerald-400/40" />
                                    <p class="text-sm text-muted-foreground">¡Sin vencidos en este periodo!</p>
                                </div>
                                <VueApexCharts
                                    v-else
                                    type="bar"
                                    :height="Math.max(220, charts.top_expired.length * 42)"
                                    :options="topExpiredOptions"
                                    :series="topExpiredSeries"
                                />
                            </div>
                        </Transition>

                        <Transition name="section-fade">
                            <div v-if="visibility.byCampus" class="rounded-2xl border border-border bg-background p-5 shadow-sm">
                                <div class="mb-4 flex items-center gap-3 border-b border-border/60 pb-3">
                                    <Send class="h-4 w-4 text-emerald-500" />
                                    <div>
                                        <h3 class="text-sm font-semibold text-foreground">Notificaciones por campus</h3>
                                        <p class="text-xs text-muted-foreground">Enviados vs. fallidos por sede</p>
                                    </div>
                                </div>
                                <div v-if="charts.by_campus.categories.length === 0" class="flex flex-col items-center gap-2 py-10 text-center">
                                    <BarChart3 class="h-10 w-10 text-muted-foreground/25" />
                                    <p class="text-sm text-muted-foreground">Sin datos por campus</p>
                                </div>
                                <VueApexCharts
                                    v-else
                                    type="bar"
                                    height="280"
                                    :options="byCampusOptions"
                                    :series="byCampusSeries"
                                />
                            </div>
                        </Transition>
                    </div>

                    <!-- ══════════════════════════════════
                         FILA 4: Eval por campus (full)
                    ═══════════════════════════════════ -->
                    <Transition name="section-fade">
                        <div v-if="visibility.evalByCampus" class="rounded-2xl border border-border bg-background p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-3 border-b border-border/60 pb-3">
                                <BarChart3 class="h-4 w-4 text-violet-500" />
                                <div>
                                    <h3 class="text-sm font-semibold text-foreground">Evaluaciones por campus</h3>
                                    <p class="text-xs text-muted-foreground">Componentes evaluados vs. vencidos por sede académica</p>
                                </div>
                            </div>
                            <div v-if="charts.eval_by_campus.categories.length === 0" class="flex flex-col items-center gap-2 py-10 text-center">
                                <BarChart3 class="h-10 w-10 text-muted-foreground/25" />
                                <p class="text-sm text-muted-foreground">Sin datos de evaluación por campus</p>
                            </div>
                            <VueApexCharts
                                v-else
                                type="bar"
                                height="260"
                                :options="evalByCampusOptions"
                                :series="evalByCampusSeries"
                            />
                        </div>
                    </Transition>

                </template>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Settings panel */
.settings-panel-enter-active,
.settings-panel-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.settings-panel-enter-from,
.settings-panel-leave-to { opacity: 0; transform: translateY(-8px); }

/* Section fade */
.section-fade-enter-active,
.section-fade-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.section-fade-enter-from,
.section-fade-leave-to { opacity: 0; transform: translateY(6px); }
</style>
