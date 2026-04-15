<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    BarChart2,
    Building2,
    CalendarDays,
    GraduationCap,
    Mail,
    RefreshCw,
    TrendingUp,
    User,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

/* =========================
   Types
========================= */
interface Campus { id: number; name: string }
interface RankItem { name: string; total: number }
interface TeacherItem { name: string; dni: string; total: number }

/* =========================
   Props
========================= */
const props = defineProps<{
    filters: { campus_id: number | null; status: string | null };
    currentPeriodName: string | null;
    hasPeriod: boolean;
    campusList: Campus[];
    dailyLimit: number;
    sentToday: number;
    remaining: number;
    totalNotified: number;
    totalNotNotified: number;
    byFaculty: RankItem[];
    byProgram: RankItem[];
    topTeachers: TeacherItem[];
}>();

/* =========================
   Filters
========================= */
const form = ref({
    campus_id: props.filters.campus_id ?? '',
    status: props.filters.status ?? '',
});

const applyFilters = () => {
    router.get(route('admin.reports.index'), form.value, { preserveState: true });
};

const clearFilters = () => {
    form.value = { campus_id: '', status: '' };
    router.get(route('admin.reports.index'), {}, { preserveState: true });
};

/* =========================
   Daily gauge
========================= */
const sentPercent = computed(() =>
    props.dailyLimit > 0 ? Math.min(100, Math.round((props.sentToday / props.dailyLimit) * 100)) : 0,
);

const gaugeColor = computed(() => {
    if (sentPercent.value >= 90) return '#dc2626';
    if (sentPercent.value >= 70) return '#f59e0b';
    return '#10b981';
});

/* =========================
   Donut chart (notificados vs no)
========================= */
const donutSeries = computed(() => [props.totalNotified, props.totalNotNotified]);
const donutOptions = computed(() => ({
    chart: { type: 'donut', fontFamily: 'inherit' },
    labels: ['Notificados', 'No notificados'],
    colors: ['#10b981', '#e5e7eb'],
    legend: { position: 'bottom', fontSize: '12px' },
    plotOptions: { pie: { donut: { size: '68%' } } },
    dataLabels: { enabled: false },
    tooltip: { y: { formatter: (v: number) => `${v} docente${v !== 1 ? 's' : ''}` } },
}));

/* =========================
   Bar chart — Facultad
========================= */
const facultyCategories = computed(() => props.byFaculty.map((f) => f.name));
const facultySeries = computed(() => [{ name: 'Docentes notificados', data: props.byFaculty.map((f) => f.total) }]);
const barOptions = (color: string) => ({
    chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, dataLabels: { position: 'top' } } },
    dataLabels: { enabled: true, offsetX: 6, style: { fontSize: '11px', colors: ['#374151'] } },
    colors: [color],
    xaxis: { labels: { style: { fontSize: '11px' } } },
    yaxis: { labels: { style: { fontSize: '11px' }, maxWidth: 180 } },
    grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
    tooltip: { y: { formatter: (v: number) => `${v} docente${v !== 1 ? 's' : ''}` } },
});

const facultyOptions = computed(() => barOptions('#087ab1'));

/* =========================
   Bar chart — Escuela Profesional
========================= */
const programCategories = computed(() => props.byProgram.map((p) => p.name));
const programSeries = computed(() => [{ name: 'Docentes notificados', data: props.byProgram.map((p) => p.total) }]);
const programOptions = computed(() => barOptions('#7c3aed'));
</script>

<template>
    <Head title="Reportes" />

    <AppLayout>
        <div class="space-y-6 px-6 py-6">

            <!-- HEADER -->
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#087ab1] shadow-md">
                    <BarChart2 class="h-5 w-5 text-white" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Reportes</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Dashboard de métricas de notificaciones
                        <template v-if="currentPeriodName">
                            —
                            <span class="font-medium text-[#087ab1]">{{ currentPeriodName }}</span>
                        </template>
                    </p>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 bg-linear-to-r from-gray-50 to-gray-100 px-5 py-3 dark:border-gray-700 dark:from-gray-800 dark:to-gray-800">
                    <CalendarDays class="h-3.5 w-3.5 text-[#087ab1]" />
                    <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Filtros</span>
                </div>
                <div class="flex flex-wrap items-end gap-3 px-5 py-4">
                    <!-- Campus -->
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Campus</label>
                        <select
                            v-model="form.campus_id"
                            class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 transition focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                        >
                            <option value="">Todos los campus</option>
                            <option v-for="c in campusList" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <!-- Estado -->
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Estado</label>
                        <select
                            v-model="form.status"
                            class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 transition focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                        >
                            <option value="">Todos</option>
                            <option value="notified">Notificados</option>
                            <option value="not_notified">No notificados</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button
                            @click="applyFilters"
                            class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-4 py-2 text-sm font-medium text-white shadow-md transition hover:from-[#066a98] hover:to-[#4fbdf5] active:scale-95"
                        >
                            <RefreshCw class="h-3.5 w-3.5" />
                            Aplicar
                        </button>
                        <button
                            v-if="form.campus_id || form.status"
                            @click="clearFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-500 active:scale-95 dark:border-gray-700 dark:bg-gray-800"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- SIN PERIODO CONFIGURADO -->
            <div
                v-if="!hasPeriod"
                class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-gray-300 bg-gray-50 py-16 text-center dark:border-gray-700 dark:bg-gray-900/40"
            >
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10">
                    <BarChart2 class="h-7 w-7 text-[#087ab1]/50" />
                </div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    No hay un periodo académico activo. Selecciona uno desde el selector del encabezado.
                </p>
            </div>

            <template v-else>
                <!-- ── FILA 1: Correos diarios + Notificados vs No ── -->
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                    <!-- Enviados hoy -->
                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/30">
                            <Mail class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Enviados hoy</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ sentToday }}</p>
                            <p class="text-xs text-gray-400">de {{ dailyLimit }} límite diario</p>
                        </div>
                    </div>

                    <!-- Restantes -->
                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                            :class="remaining === 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-blue-100 dark:bg-blue-900/30'"
                        >
                            <TrendingUp
                                class="h-6 w-6"
                                :class="remaining === 0 ? 'text-red-500' : 'text-blue-500 dark:text-blue-400'"
                            />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Restantes hoy</p>
                            <p
                                class="text-2xl font-bold"
                                :class="remaining === 0 ? 'text-red-600' : 'text-gray-900 dark:text-gray-100'"
                            >
                                {{ remaining }}
                            </p>
                            <p class="text-xs" :class="remaining === 0 ? 'text-red-400' : 'text-gray-400'">
                                {{ remaining === 0 ? 'Límite alcanzado' : `${sentPercent}% usado` }}
                            </p>
                        </div>
                    </div>

                    <!-- Total notificados (periodo) -->
                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-900/30">
                            <Users class="h-6 w-6 text-violet-600 dark:text-violet-400" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Notificados (periodo)</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ totalNotified }}</p>
                            <p class="text-xs text-gray-400">docentes únicos</p>
                        </div>
                    </div>

                    <!-- No notificados -->
                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30">
                            <User class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">No notificados</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ totalNotNotified }}</p>
                            <p class="text-xs text-gray-400">pendientes en lotes</p>
                        </div>
                    </div>
                </div>

                <!-- Barra de uso diario -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Uso del límite diario ({{ sentToday }}/{{ dailyLimit }})</span>
                        <span class="text-sm font-bold" :style="{ color: gaugeColor }">{{ sentPercent }}%</span>
                    </div>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :style="{ width: `${sentPercent}%`, backgroundColor: gaugeColor }"
                        />
                    </div>
                    <div class="mt-1.5 flex justify-between text-xs text-gray-400">
                        <span>0</span>
                        <span>{{ Math.round(dailyLimit / 2) }}</span>
                        <span>{{ dailyLimit }}</span>
                    </div>
                </div>

                <!-- ── FILA 2: Donut + Top Docentes ── -->
                <div class="grid gap-4 lg:grid-cols-2">

                    <!-- Donut notificados vs no -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="mb-4 flex items-center gap-2">
                            <Users class="h-4 w-4 text-[#087ab1]" />
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Notificados vs No notificados</h2>
                        </div>
                        <div v-if="totalNotified + totalNotNotified > 0">
                            <VueApexCharts
                                type="donut"
                                height="260"
                                :series="donutSeries"
                                :options="donutOptions"
                            />
                        </div>
                        <div v-else class="flex h-48 items-center justify-center text-sm text-gray-400">
                            Sin datos para el periodo seleccionado
                        </div>
                    </div>

                    <!-- Ranking docentes -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="mb-4 flex items-center gap-2">
                            <User class="h-4 w-4 text-[#087ab1]" />
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Docentes con más notificaciones</h2>
                        </div>
                        <div v-if="topTeachers.length > 0" class="space-y-2">
                            <div
                                v-for="(t, i) in topTeachers"
                                :key="i"
                                class="flex items-center gap-3"
                            >
                                <span
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white"
                                    :class="i === 0 ? 'bg-amber-400' : i === 1 ? 'bg-gray-400' : i === 2 ? 'bg-amber-700' : 'bg-gray-200 text-gray-600'"
                                >
                                    {{ i + 1 }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-medium text-gray-800 dark:text-gray-100">{{ t.name }}</p>
                                    <p class="text-[10px] text-gray-400">DNI: {{ t.dni }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-[#087ab1]/10 px-2 py-0.5 text-xs font-semibold text-[#087ab1] dark:bg-[#087ab1]/20 dark:text-[#68c8fb]">
                                    {{ t.total }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="flex h-48 items-center justify-center text-sm text-gray-400">
                            Sin datos para el periodo seleccionado
                        </div>
                    </div>
                </div>

                <!-- ── FILA 3: Facultades ── -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="mb-4 flex items-center gap-2">
                        <Building2 class="h-4 w-4 text-[#087ab1]" />
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Facultades con más docentes notificados</h2>
                    </div>
                    <div v-if="byFaculty.length > 0">
                        <VueApexCharts
                            type="bar"
                            :height="Math.max(180, byFaculty.length * 42)"
                            :series="facultySeries"
                            :options="{ ...facultyOptions, xaxis: { ...facultyOptions.xaxis, categories: facultyCategories } }"
                        />
                    </div>
                    <div v-else class="flex h-32 items-center justify-center text-sm text-gray-400">
                        Sin datos de facultades para el periodo seleccionado
                    </div>
                </div>

                <!-- ── FILA 4: Escuelas profesionales ── -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="mb-4 flex items-center gap-2">
                        <GraduationCap class="h-4 w-4 text-violet-600" />
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Escuelas profesionales con más docentes notificados</h2>
                    </div>
                    <div v-if="byProgram.length > 0">
                        <VueApexCharts
                            type="bar"
                            :height="Math.max(180, byProgram.length * 42)"
                            :series="programSeries"
                            :options="{ ...programOptions, xaxis: { ...programOptions.xaxis, categories: programCategories } }"
                        />
                    </div>
                    <div v-else class="flex h-32 items-center justify-center text-sm text-gray-400">
                        Sin datos de escuelas para el periodo seleccionado
                    </div>
                </div>

            </template>
        </div>
    </AppLayout>
</template>
