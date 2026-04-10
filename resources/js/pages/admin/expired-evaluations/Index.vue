<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, BookOpen, Sparkles } from 'lucide-vue-next';

defineProps<{
    records: any;
    activeBatchId?: number;
}>();

const goToPage = (url: string | null) => {
    if (!url) return;
    router.visit(url, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <Head title="Docentes con Rubros Vencidos" />

    <AppLayout>
        <div class="space-y-6 px-6 py-6">

            <!-- HEADER -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#087ab1] shadow-md">
                        <AlertTriangle class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Docentes con Rubros Vencidos
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Todos los registros importados. El lote activo se usará para futuras notificaciones.
                        </p>
                    </div>
                </div>

                <!-- Badge lote activo -->
                <div
                    v-if="activeBatchId"
                    class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 shadow-sm dark:border-emerald-700 dark:bg-emerald-900/30"
                >
                    <Sparkles class="h-4 w-4 text-emerald-500" />
                    <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                        Último lote activo:
                    </span>
                    <span class="rounded-full bg-emerald-500 px-2 py-0.5 text-xs font-bold text-white">
                        #{{ activeBatchId }}
                    </span>
                </div>
            </div>

            <!-- TABLA -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">

                        <thead>
                            <tr class="bg-linear-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800">
                                <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                    Estado
                                </th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                    Lote
                                </th>
                                <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase sm:table-cell dark:text-gray-400">
                                    Fecha Carga
                                </th>
                                <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase md:table-cell dark:text-gray-400">
                                    Periodo
                                </th>
                                <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400">
                                    Campus
                                </th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                    Docente
                                </th>
                                <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase md:table-cell dark:text-gray-400">
                                    Curso
                                </th>
                                <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400">
                                    Ciclo
                                </th>
                                <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400">
                                    Grupo
                                </th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                    Rubros Vencidos
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">

                            <tr
                                v-for="row in records.data"
                                :key="row.id"
                                class="transition-all duration-150"
                                :class="row.is_active_batch
                                    ? 'bg-emerald-50/70 hover:bg-emerald-50 hover:shadow-[inset_3px_0_0_#10b981] dark:bg-emerald-900/10 dark:hover:bg-emerald-900/20 dark:hover:shadow-[inset_3px_0_0_#34d399]'
                                    : 'hover:bg-[#68c8fb]/5 hover:shadow-[inset_3px_0_0_#087ab1] dark:hover:bg-[#68c8fb]/10 dark:hover:shadow-[inset_3px_0_0_#68c8fb]'
                                "
                            >
                                <!-- Estado -->
                                <td class="px-5 py-3.5">
                                    <span
                                        v-if="row.is_active_batch"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-400 dark:ring-emerald-700"
                                    >
                                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500" />
                                        Activo
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700"
                                    >
                                        Histórico
                                    </span>
                                </td>

                                <!-- Lote -->
                                <td class="px-5 py-3.5">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-xs font-bold"
                                        :class="row.is_active_batch
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                    >
                                        # {{ row.import_batch_id }}
                                    </span>
                                </td>

                                <!-- Fecha Carga -->
                                <td class="hidden px-5 py-3.5 sm:table-cell">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ row.imported_at }}
                                    </span>
                                </td>

                                <!-- Periodo -->
                                <td class="hidden px-5 py-3.5 md:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ row.academic_period }}
                                    </span>
                                </td>

                                <!-- Campus -->
                                <td class="hidden px-5 py-3.5 lg:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ row.campus }}
                                    </span>
                                </td>

                                <!-- Docente -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                            :class="row.is_active_batch
                                                ? 'bg-emerald-100 dark:bg-emerald-900/40'
                                                : 'bg-[#087ab1]/10'"
                                        >
                                            <BookOpen
                                                class="h-4 w-4"
                                                :class="row.is_active_batch ? 'text-emerald-600' : 'text-[#087ab1]'"
                                            />
                                        </div>
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                            {{ row.teacher }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Curso -->
                                <td class="hidden px-5 py-3.5 md:table-cell">
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ row.course }}
                                    </span>
                                </td>

                                <!-- Ciclo -->
                                <td class="hidden px-5 py-3.5 lg:table-cell">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ row.cycle }}
                                    </span>
                                </td>

                                <!-- Grupo -->
                                <td class="hidden px-5 py-3.5 lg:table-cell">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ row.group }}
                                    </span>
                                </td>

                                <!-- Rubros Vencidos -->
                                <td class="px-5 py-3.5">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                        :class="row.expired_components > 0
                                            ? 'bg-rose-50 text-rose-600 ring-1 ring-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:ring-rose-800'
                                            : 'bg-gray-50 text-gray-400 ring-1 ring-gray-200'"
                                    >
                                        {{ row.expired_components }}
                                    </span>
                                </td>
                            </tr>

                            <!-- Sin datos -->
                            <tr v-if="records.data.length === 0">
                                <td colspan="10" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10 dark:bg-[#087ab1]/20">
                                            <AlertTriangle class="h-7 w-7 text-[#087ab1]/60" />
                                        </div>
                                        <p class="text-sm font-medium text-gray-500">
                                            No existen registros cargados aún
                                        </p>
                                    </div>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PAGINACIÓN -->
            <div v-if="records.links.length > 3" class="flex justify-end">
                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="link in records.links"
                        :key="link.label"
                        v-html="link.label"
                        @click="goToPage(link.url)"
                        :disabled="!link.url"
                        class="rounded-lg border px-3 py-1.5 text-sm font-medium transition"
                        :class="[
                            link.active
                                ? 'border-[#087ab1] bg-[#087ab1] text-white shadow-sm'
                                : 'border-gray-200 bg-white text-gray-600 hover:border-[#087ab1]/30 hover:bg-[#087ab1]/5 hover:text-[#087ab1] dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300',
                            !link.url && 'cursor-not-allowed opacity-40',
                        ]"
                    />
                </div>
            </div>

        </div>
    </AppLayout>
</template>
