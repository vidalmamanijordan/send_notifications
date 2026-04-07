<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    BarChart2,
    BookOpen,
    CalendarDays,
    CheckCircle2,
    CheckCircle,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Edit3,
    Hash,
    Mail,
    Save,
    UserRound,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

/* ─────────────────────────────────────────
   TYPES
───────────────────────────────────────── */
interface EvaluationStatus {
    id: number;
    course?: { id: number; name: string; code: string };
    academic_period?: { id: number; name: string };
    campus?: { id: number; name: string };
    cycle: string | null;
    group: string | null;
    total_components: number;
    evaluated_components: number;
    expired_components: number;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Teacher {
    id: number;
    dni: string;
    full_name: string;
    email: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface Stats {
    total_records: number;
    total_components: number;
    evaluated_components: number;
    expired_components: number;
}

/* ─────────────────────────────────────────
   PROPS
───────────────────────────────────────── */
const props = defineProps<{
    teacher: Teacher;
    evaluationStatuses: {
        data: EvaluationStatus[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
    };
    stats: Stats;
    currentPeriod: { id: number; name: string; status: string } | null;
}>();

/* ─────────────────────────────────────────
   FLASH
───────────────────────────────────────── */
const page = usePage();
const flash = computed(() => (page.props as any).flash as { success?: string; error?: string });
const showFlash = ref(false);

watch(
    () => flash.value?.success,
    (val) => {
        if (val) {
            showFlash.value = true;
            setTimeout(() => (showFlash.value = false), 4000);
        }
    },
    { immediate: true },
);

/* ─────────────────────────────────────────
   EDIT FORM
───────────────────────────────────────── */
const isEditing = ref(false);

const form = useForm({
    full_name: props.teacher.full_name,
    email: props.teacher.email ?? '',
    is_active: props.teacher.is_active,
});

function startEdit() {
    form.full_name = props.teacher.full_name;
    form.email = props.teacher.email ?? '';
    form.is_active = props.teacher.is_active;
    isEditing.value = true;
}

function cancelEdit() {
    form.reset();
    form.clearErrors();
    isEditing.value = false;
}

function save() {
    form.patch(route('admin.teachers.update', props.teacher.id), {
        onSuccess: () => {
            isEditing.value = false;
        },
    });
}

/* ─────────────────────────────────────────
   HELPERS
───────────────────────────────────────── */
function getInitials(name: string): string {
    return name
        .split(' ')
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
}

function progressPct(evaluated: number, total: number): number {
    if (!total) return 0;
    return Math.min(Math.round((evaluated / total) * 100), 100);
}

function expiredPct(expired: number, total: number): number {
    if (!total) return 0;
    return Math.min(Math.round((expired / total) * 100), 100);
}

function formatDate(d: string): string {
    return new Date(d).toLocaleDateString('es-PE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function goBack() {
    router.visit(route('dashboard'));
}

function paginate(url: string | null) {
    if (!url) return;
    router.visit(url, { preserveScroll: true });
}

const completionRate = computed(() => {
    if (!props.stats.total_components) return 0;
    return Math.round((props.stats.evaluated_components / props.stats.total_components) * 100);
});

const hasEmail = computed(() => !!props.teacher.email);
</script>

<template>
    <Head :title="`Docente: ${teacher.full_name}`" />

    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: route('dashboard') },
            { title: 'Docentes', href: '#' },
            { title: teacher.full_name, href: '#' },
        ]"
    >
        <div class="min-h-screen bg-gray-50/50 dark:bg-background">
            <!-- ── FLASH ── -->
            <Transition name="flash-slide">
                <div
                    v-if="showFlash && flash?.success"
                    class="fixed right-4 top-4 z-50 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-lg dark:border-emerald-800 dark:bg-emerald-900/40"
                >
                    <CheckCircle class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                    <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300">{{ flash.success }}</p>
                    <button @click="showFlash = false" class="ml-2 text-emerald-500 hover:text-emerald-700">
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </Transition>

            <div class="mx-auto max-w-6xl space-y-6 p-6">

                <!-- ── BACK BUTTON ── -->
                <button
                    @click="goBack"
                    class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Volver al dashboard
                </button>

                <!-- ══════════════════════════════════════
                     HERO CARD
                ════════════════════════════════════════ -->
                <div class="overflow-hidden rounded-2xl shadow-lg">
                    <!-- Gradient header -->
                    <div class="relative bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-700 px-8 py-8">
                        <!-- Decorative circles -->
                        <div class="pointer-events-none absolute -right-12 -top-12 h-48 w-48 rounded-full bg-white/5" />
                        <div class="pointer-events-none absolute -bottom-8 right-32 h-32 w-32 rounded-full bg-white/5" />

                        <div class="relative flex flex-col items-start gap-6 sm:flex-row sm:items-center">
                            <!-- Avatar -->
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-2xl font-bold text-white shadow-inner backdrop-blur-sm ring-2 ring-white/30">
                                {{ getInitials(teacher.full_name) }}
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h1 class="text-2xl font-bold text-white leading-tight">
                                        {{ teacher.full_name }}
                                    </h1>
                                    <!-- Active badge -->
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="teacher.is_active
                                            ? 'bg-emerald-400/20 text-emerald-200 ring-1 ring-emerald-400/40'
                                            : 'bg-red-400/20 text-red-200 ring-1 ring-red-400/40'"
                                    >
                                        <CheckCircle2 v-if="teacher.is_active" class="h-3 w-3" />
                                        <XCircle v-else class="h-3 w-3" />
                                        {{ teacher.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-4">
                                    <span class="flex items-center gap-1.5 text-sm text-indigo-200">
                                        <Hash class="h-3.5 w-3.5" />
                                        DNI: <strong class="text-white">{{ teacher.dni }}</strong>
                                    </span>
                                    <span
                                        class="flex items-center gap-1.5 text-sm"
                                        :class="hasEmail ? 'text-indigo-200' : 'text-red-300'"
                                    >
                                        <Mail class="h-3.5 w-3.5" />
                                        <span v-if="hasEmail" class="text-white">{{ teacher.email }}</span>
                                        <span v-else class="italic">Sin correo registrado</span>
                                    </span>
                                </div>

                                <p class="mt-1.5 text-xs text-indigo-300">
                                    Registrado el {{ formatDate(teacher.created_at) }}
                                    · Actualizado {{ formatDate(teacher.updated_at) }}
                                </p>
                            </div>

                            <!-- Edit button -->
                            <button
                                v-if="!isEditing"
                                @click="startEdit"
                                class="flex shrink-0 items-center gap-2 rounded-xl bg-white/15 px-4 py-2 text-sm font-medium text-white backdrop-blur-sm ring-1 ring-white/20 transition hover:bg-white/25"
                            >
                                <Edit3 class="h-4 w-4" />
                                Editar datos
                            </button>
                        </div>
                    </div>

                    <!-- ── PERIODO CONTEXT BAR ── -->
                    <div class="flex items-center justify-center gap-2 border-b border-border/60 bg-muted/20 px-6 py-2">
                        <CalendarDays class="h-3.5 w-3.5 text-muted-foreground" />
                        <span class="text-xs text-muted-foreground">
                            Datos del periodo:
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                            <span class="relative flex h-1.5 w-1.5">
                                <span v-if="currentPeriod?.status === 'active'" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75" />
                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full" :class="currentPeriod?.status === 'active' ? 'bg-emerald-500' : 'bg-indigo-400'" />
                            </span>
                            {{ currentPeriod?.name ?? 'Sin periodo' }}
                        </span>
                    </div>

                    <!-- ── STATS STRIP ── -->
                    <div class="grid grid-cols-2 divide-x divide-border bg-background sm:grid-cols-4">
                        <div class="flex flex-col items-center gap-1 px-6 py-4">
                            <ClipboardList class="h-5 w-5 text-indigo-500" />
                            <span class="text-2xl font-bold text-foreground">{{ stats.total_records }}</span>
                            <span class="text-xs text-muted-foreground text-center">Registros de evaluación</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 px-6 py-4">
                            <BookOpen class="h-5 w-5 text-blue-500" />
                            <span class="text-2xl font-bold text-foreground">{{ stats.total_components }}</span>
                            <span class="text-xs text-muted-foreground text-center">Componentes totales</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 px-6 py-4">
                            <BarChart2 class="h-5 w-5 text-emerald-500" />
                            <span class="text-2xl font-bold text-foreground">{{ stats.evaluated_components }}</span>
                            <span class="text-xs text-muted-foreground text-center">Evaluados</span>
                        </div>
                        <div class="flex flex-col items-center gap-1 px-6 py-4">
                            <AlertTriangle class="h-5 w-5 text-red-500" />
                            <span
                                class="text-2xl font-bold"
                                :class="stats.expired_components > 0 ? 'text-red-600 dark:text-red-400' : 'text-foreground'"
                            >
                                {{ stats.expired_components }}
                            </span>
                            <span class="text-xs text-muted-foreground text-center">Vencidos</span>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════
                     MAIN GRID
                ════════════════════════════════════════ -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                    <!-- ── EDIT FORM ── -->
                    <div class="lg:col-span-1">
                        <div class="rounded-2xl border border-border bg-background shadow-sm">
                            <!-- Card header -->
                            <div class="flex items-center gap-3 border-b border-border px-6 py-4">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                                    <UserRound class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-foreground">Información del docente</h2>
                                    <p class="text-xs text-muted-foreground">Datos editables del perfil</p>
                                </div>
                            </div>

                            <div class="p-6 space-y-5">
                                <!-- DNI (read-only) -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-muted-foreground uppercase tracking-wide">
                                        DNI
                                    </label>
                                    <div class="flex items-center gap-2 rounded-lg border border-border/60 bg-muted/40 px-3 py-2.5">
                                        <Hash class="h-4 w-4 shrink-0 text-muted-foreground" />
                                        <span class="text-sm font-mono font-medium text-foreground">{{ teacher.dni }}</span>
                                        <span class="ml-auto rounded-md bg-muted px-2 py-0.5 text-[10px] text-muted-foreground">Solo lectura</span>
                                    </div>
                                </div>

                                <!-- Full Name -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-muted-foreground uppercase tracking-wide">
                                        Nombre completo <span class="text-red-500">*</span>
                                    </label>
                                    <div v-if="!isEditing" class="flex items-center gap-2 rounded-lg border border-border/60 bg-muted/40 px-3 py-2.5">
                                        <UserRound class="h-4 w-4 shrink-0 text-muted-foreground" />
                                        <span class="text-sm text-foreground">{{ teacher.full_name }}</span>
                                    </div>
                                    <div v-else>
                                        <input
                                            v-model="form.full_name"
                                            type="text"
                                            class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none transition-all"
                                            :class="form.errors.full_name
                                                ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-300 dark:bg-red-900/10'
                                                : 'border-border bg-background focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 dark:focus:ring-indigo-900'"
                                            placeholder="Nombre completo del docente"
                                        />
                                        <p v-if="form.errors.full_name" class="mt-1 text-xs text-red-500">{{ form.errors.full_name }}</p>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-muted-foreground uppercase tracking-wide">
                                        Correo electrónico
                                        <span v-if="!hasEmail" class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">
                                            Pendiente
                                        </span>
                                    </label>
                                    <div v-if="!isEditing" class="flex items-center gap-2 rounded-lg border px-3 py-2.5"
                                        :class="hasEmail
                                            ? 'border-border/60 bg-muted/40'
                                            : 'border-amber-300/60 bg-amber-50/60 dark:border-amber-700/40 dark:bg-amber-900/10'"
                                    >
                                        <Mail class="h-4 w-4 shrink-0 text-muted-foreground" />
                                        <span v-if="hasEmail" class="text-sm text-foreground">{{ teacher.email }}</span>
                                        <span v-else class="text-sm italic text-amber-600 dark:text-amber-400">Sin correo — haz clic en Editar</span>
                                    </div>
                                    <div v-else>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none transition-all"
                                            :class="form.errors.email
                                                ? 'border-red-400 bg-red-50 focus:ring-2 focus:ring-red-300 dark:bg-red-900/10'
                                                : 'border-border bg-background focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 dark:focus:ring-indigo-900'"
                                            placeholder="correo@universidad.edu"
                                        />
                                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-muted-foreground uppercase tracking-wide">
                                        Estado del docente
                                    </label>
                                    <div v-if="!isEditing" class="flex items-center gap-2 rounded-lg border border-border/60 bg-muted/40 px-3 py-2.5">
                                        <CheckCircle2 v-if="teacher.is_active" class="h-4 w-4 text-emerald-500" />
                                        <XCircle v-else class="h-4 w-4 text-red-500" />
                                        <span class="text-sm text-foreground">{{ teacher.is_active ? 'Activo' : 'Inactivo' }}</span>
                                    </div>
                                    <div v-else class="flex items-center gap-3">
                                        <button
                                            type="button"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2"
                                            :class="form.is_active ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600'"
                                            @click="form.is_active = !form.is_active"
                                        >
                                            <span
                                                class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                                                :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                                            />
                                        </button>
                                        <span class="text-sm font-medium" :class="form.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500'">
                                            {{ form.is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Completion bar -->
                                <div v-if="stats.total_components > 0" class="rounded-xl border border-border/60 bg-muted/30 p-4">
                                    <div class="mb-2 flex items-center justify-between">
                                        <span class="text-xs font-medium text-muted-foreground">Progreso de evaluación</span>
                                        <span class="text-xs font-bold text-foreground">{{ completionRate }}%</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div
                                            class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-700"
                                            :style="{ width: `${completionRate}%` }"
                                        />
                                    </div>
                                    <p class="mt-2 text-xs text-muted-foreground">
                                        {{ stats.evaluated_components }} de {{ stats.total_components }} componentes evaluados
                                    </p>
                                </div>

                                <!-- Action buttons -->
                                <div v-if="isEditing" class="flex gap-2 pt-1">
                                    <button
                                        @click="save"
                                        :disabled="form.processing"
                                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-60"
                                    >
                                        <Save class="h-4 w-4" />
                                        {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                                    </button>
                                    <button
                                        @click="cancelEdit"
                                        :disabled="form.processing"
                                        class="flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── EVALUATION TABLE ── -->
                    <div class="lg:col-span-2">
                        <div class="rounded-2xl border border-border bg-background shadow-sm">
                            <!-- Card header -->
                            <div class="flex items-center justify-between gap-3 border-b border-border px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-900/40">
                                        <ClipboardList class="h-4 w-4 text-violet-600 dark:text-violet-400" />
                                    </div>
                                    <div>
                                        <h2 class="text-sm font-semibold text-foreground">Historial de evaluaciones</h2>
                                        <p class="text-xs text-muted-foreground">
                                            {{ evaluationStatuses.total }} registro{{ evaluationStatuses.total !== 1 ? 's' : '' }} en el periodo
                                        </p>
                                    </div>
                                </div>
                                <span v-if="currentPeriod" class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                    <CalendarDays class="h-3 w-3" />
                                    {{ currentPeriod.name }}
                                </span>
                            </div>

                            <!-- Table -->
                            <div class="overflow-x-auto">
                                <table v-if="evaluationStatuses.data.length > 0" class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-border/60 bg-muted/30">
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Curso</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Periodo</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Campus</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Ciclo / Gr.</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Progreso</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border/40">
                                        <tr
                                            v-for="ev in evaluationStatuses.data"
                                            :key="ev.id"
                                            class="transition-colors hover:bg-muted/30"
                                            :class="ev.expired_components > 0 ? 'bg-red-50/40 dark:bg-red-900/5' : ''"
                                        >
                                            <!-- Curso -->
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                                                        <BookOpen class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="truncate text-xs font-medium text-foreground max-w-[140px]">
                                                            {{ ev.course?.name ?? '—' }}
                                                        </p>
                                                        <p class="text-[10px] text-muted-foreground font-mono">{{ ev.course?.code ?? '' }}</p>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Periodo -->
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                                    {{ ev.academic_period?.name ?? '—' }}
                                                </span>
                                            </td>

                                            <!-- Campus -->
                                            <td class="px-4 py-3">
                                                <span class="text-xs text-muted-foreground">{{ ev.campus?.name ?? '—' }}</span>
                                            </td>

                                            <!-- Ciclo / Grupo -->
                                            <td class="px-4 py-3 text-center">
                                                <span class="font-mono text-xs text-foreground">
                                                    {{ ev.cycle ?? '—' }} / {{ ev.group ?? '—' }}
                                                </span>
                                            </td>

                                            <!-- Progreso -->
                                            <td class="px-4 py-3">
                                                <div class="min-w-[120px] space-y-1.5">
                                                    <!-- Evaluados -->
                                                    <div>
                                                        <div class="mb-1 flex items-center justify-between">
                                                            <span class="text-[10px] text-muted-foreground">Evaluados</span>
                                                            <span class="text-[10px] font-medium text-foreground">
                                                                {{ ev.evaluated_components }}/{{ ev.total_components }}
                                                            </span>
                                                        </div>
                                                        <div class="h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                                            <div
                                                                class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                                                :style="{ width: `${progressPct(ev.evaluated_components, ev.total_components)}%` }"
                                                            />
                                                        </div>
                                                    </div>

                                                    <!-- Vencidos -->
                                                    <div v-if="ev.expired_components > 0">
                                                        <div class="mb-1 flex items-center justify-between">
                                                            <span class="flex items-center gap-1 text-[10px] text-red-500">
                                                                <AlertTriangle class="h-2.5 w-2.5" />
                                                                Vencidos
                                                            </span>
                                                            <span class="text-[10px] font-bold text-red-600 dark:text-red-400">
                                                                {{ ev.expired_components }}
                                                            </span>
                                                        </div>
                                                        <div class="h-1.5 overflow-hidden rounded-full bg-red-100 dark:bg-red-900/30">
                                                            <div
                                                                class="h-full rounded-full bg-red-500 transition-all duration-500"
                                                                :style="{ width: `${expiredPct(ev.expired_components, ev.total_components)}%` }"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Empty state -->
                                <div v-else class="flex flex-col items-center gap-3 py-16 text-center">
                                    <ClipboardList class="h-12 w-12 text-muted-foreground/25" />
                                    <p class="text-sm font-medium text-muted-foreground">Sin registros de evaluación</p>
                                    <p class="text-xs text-muted-foreground/60">Este docente no tiene evaluaciones registradas aún.</p>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div
                                v-if="evaluationStatuses.last_page > 1"
                                class="flex items-center justify-between border-t border-border px-6 py-3"
                            >
                                <p class="text-xs text-muted-foreground">
                                    Mostrando {{ evaluationStatuses.from }}–{{ evaluationStatuses.to }}
                                    de {{ evaluationStatuses.total }}
                                </p>
                                <div class="flex items-center gap-1">
                                    <button
                                        v-for="link in evaluationStatuses.links"
                                        :key="link.label"
                                        :disabled="!link.url"
                                        @click="paginate(link.url)"
                                        class="flex h-8 min-w-[32px] items-center justify-center rounded-lg px-2 text-xs font-medium transition-colors"
                                        :class="[
                                            link.active
                                                ? 'bg-indigo-600 text-white shadow-sm'
                                                : link.url
                                                    ? 'text-muted-foreground hover:bg-muted'
                                                    : 'cursor-not-allowed text-muted-foreground/40',
                                        ]"
                                        v-html="link.label"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.flash-slide-enter-active,
.flash-slide-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.flash-slide-enter-from,
.flash-slide-leave-to {
    opacity: 0;
    transform: translateY(-12px) scale(0.97);
}
</style>
