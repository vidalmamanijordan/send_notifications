<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Eye, FileSpreadsheet, Search, UserRound, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';

/* =========================
   Interfaces
========================= */
interface Teacher {
    id: number;
    dni: string;
    full_name: string;
    email: string | null;
    is_active: boolean;
    created_at: string;
    evaluation_statuses_sum_expired_components: number | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

/* =========================
   Props
========================= */
const props = defineProps<{
    teachers: {
        data: Teacher[];
        links: PaginationLink[];
        total?: number;
        from?: number;
        to?: number;
    };
    filters: { search: string };
    currentPeriod: { id: number; name: string } | null;
}>();

/* =========================
   Helpers
========================= */
const translateLabel = (label: string): string =>
    label.replace('Previous', 'Anterior').replace('Next', 'Siguiente');

const isPageNumber = (label: string): boolean =>
    /^\d+$/.test(label.replace(/&[^;]+;/g, '').trim());

const formatDate = (date: string): string =>
    new Date(date).toLocaleDateString('es-PE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

/* =========================
   Búsqueda
========================= */
const search = ref(props.filters.search ?? '');

let debounceTimer: ReturnType<typeof setTimeout>;

watch(search, (val) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(
            route('admin.teachers.index'),
            { search: val || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 350);
});

const clearSearch = () => {
    search.value = '';
};

/* =========================
   Paginación
========================= */
const goToPage = (url: string | null) => {
    if (!url) return;
    router.visit(url, { preserveScroll: true, preserveState: true });
};
</script>

<template>
    <Head title="Docentes" />

    <AppLayout>
        <div class="space-y-6 px-6 py-6">

            <!-- HEADER -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#087ab1] shadow-md">
                        <UserRound class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Docentes
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ teachers.total ?? teachers.data.length }}
                            docente{{ (teachers.total ?? teachers.data.length) !== 1 ? 's' : '' }}
                            registrado{{ (teachers.total ?? teachers.data.length) !== 1 ? 's' : '' }}
                            <template v-if="currentPeriod">
                                · periodo
                                <span class="font-medium text-[#087ab1]">{{ currentPeriod.name }}</span>
                            </template>
                        </p>
                    </div>
                </div>

                <!-- Aviso: docentes provienen del Excel -->
                <div class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 dark:border-amber-700 dark:bg-amber-900/20">
                    <FileSpreadsheet class="h-4 w-4 shrink-0 text-amber-500" />
                    <p class="text-xs text-amber-700 dark:text-amber-400">
                        Los docentes se registran automáticamente al importar un reporte Excel.
                    </p>
                </div>
            </div>

            <!-- BUSCADOR -->
            <div class="relative max-w-sm">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por nombre, DNI o email..."
                    class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-9 text-sm text-gray-800 shadow-sm transition focus:border-[#087ab1] focus:outline-none focus:ring-2 focus:ring-[#68c8fb]/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                />
                <button
                    v-if="search"
                    @click="clearSearch"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- TABLA -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-linear-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                Estado
                            </th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                Docente
                            </th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase sm:table-cell dark:text-gray-400">
                                DNI
                            </th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase md:table-cell dark:text-gray-400">
                                Email
                            </th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400">
                                Rubros vencidos
                            </th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400">
                                Registrado
                            </th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        <tr
                            v-for="item in teachers.data"
                            :key="item.id"
                            class="group transition-all duration-150 hover:bg-[#68c8fb]/2 hover:shadow-[inset_3px_0_0_#087ab1] dark:hover:bg-[#68c8fb]/10 dark:hover:shadow-[inset_3px_0_0_#68c8fb]"
                        >
                            <!-- Estado -->
                            <td class="px-5 py-3.5">
                                <span
                                    v-if="item.is_active"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                                >
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500" />
                                    Activo
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400" />
                                    Inactivo
                                </span>
                            </td>

                            <!-- Nombre -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#087ab1]/10">
                                        <UserRound class="h-4 w-4 text-[#087ab1]" />
                                    </div>
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ item.full_name }}
                                    </span>
                                </div>
                            </td>

                            <!-- DNI -->
                            <td class="hidden px-5 py-3.5 sm:table-cell">
                                <span class="rounded-lg bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    {{ item.dni }}
                                </span>
                            </td>

                            <!-- Email -->
                            <td class="hidden px-5 py-3.5 md:table-cell">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ item.email ?? '—' }}
                                </span>
                            </td>

                            <!-- Rubros vencidos -->
                            <td class="hidden px-5 py-3.5 lg:table-cell">
                                <template v-if="currentPeriod">
                                    <span
                                        v-if="(item.evaluation_statuses_sum_expired_components ?? 0) > 0"
                                        class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-600 ring-1 ring-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:ring-rose-800"
                                    >
                                        <AlertTriangle class="h-3 w-3" />
                                        {{ item.evaluation_statuses_sum_expired_components }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 ring-1 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:ring-emerald-700"
                                    >
                                        0
                                    </span>
                                </template>
                                <span v-else class="text-xs text-gray-400">Sin periodo</span>
                            </td>

                            <!-- Registrado -->
                            <td class="hidden px-5 py-3.5 lg:table-cell">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(item.created_at) }}
                                </span>
                            </td>

                            <!-- Acciones -->
                            <td class="px-5 py-3.5 text-right">
                                <Link
                                    :href="route('admin.teachers.show', item.id)"
                                    title="Ver detalle"
                                    class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200 text-[#087ab1] hover:bg-[#087ab1] hover:text-white"
                                >
                                    <Eye class="h-3.5 w-3.5" />
                                </Link>
                            </td>
                        </tr>

                        <!-- Sin datos -->
                        <tr v-if="teachers.data.length === 0">
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10 dark:bg-[#087ab1]/20">
                                        <UserRound class="h-7 w-7 text-[#087ab1]/60" />
                                    </div>
                                    <p class="text-sm font-medium text-gray-500">
                                        {{ search ? 'No se encontraron docentes con ese criterio' : 'No hay docentes registrados aún' }}
                                    </p>
                                    <p v-if="!search" class="text-xs text-gray-400">
                                        Importa un reporte Excel para registrar docentes automáticamente.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div v-if="teachers.links && teachers.links.length > 3" class="flex justify-end">
                <nav class="inline-flex gap-1">
                    <template v-for="link in teachers.links" :key="link.label">
                        <a
                            v-if="link.url"
                            :href="link.url"
                            @click.prevent="goToPage(link.url)"
                            class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-all"
                            :class="link.active && isPageNumber(link.label)
                                ? 'border-[#68c8fb] bg-[#68c8fb] text-white shadow-sm'
                                : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'"
                            v-html="translateLabel(link.label)"
                        />
                        <span
                            v-else
                            class="cursor-not-allowed rounded-lg border border-gray-100 bg-white px-3 py-1.5 text-sm font-medium text-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-600"
                            v-html="translateLabel(link.label)"
                        />
                    </template>
                </nav>
            </div>

        </div>
    </AppLayout>
</template>
