<script setup lang="ts">
import AcademicPeriodModal from '@/components/academic-periods/AcademicPeriodModal.vue';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { CalendarDays, Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const Swal = useSwal();

const translateLabel = (label: string): string =>
    label.replace('Previous', 'Anterior').replace('Next', 'Siguiente');

const isPageNumber = (label: string): boolean =>
    /^\d+$/.test(label.replace(/&[^;]+;/g, '').trim());

const formatDate = (date: string): string =>
    new Date(date).toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });

const calcDays = (start: string, end: string): number => {
    const diff = new Date(end).getTime() - new Date(start).getTime();
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
};

interface AcademicPeriod {
    id: number;
    code: string;
    name: string;
    start_date: string;
    end_date: string;
    status: 'active' | 'closed';
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    academicPeriods: {
        data: AcademicPeriod[];
        links: PaginationLink[];
        total?: number;
    };
}>();

const showModal = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const selectedAcademicPeriod = ref<AcademicPeriod | null>(null);

const activeAction = ref<{ id: number | null; type: 'edit' | 'delete' | null }>({ id: null, type: null });

const setActive = (id: number, type: 'edit' | 'delete') => {
    activeAction.value = { id, type };
};

const resetActive = () => {
    setTimeout(() => { activeAction.value = { id: null, type: null }; }, 400);
};

const openCreate = () => {
    modalMode.value = 'create';
    selectedAcademicPeriod.value = null;
    showModal.value = true;
};

const openEdit = (item: AcademicPeriod) => {
    setActive(item.id, 'edit');
    modalMode.value = 'edit';
    selectedAcademicPeriod.value = item;
    showModal.value = true;
};

const deleteAcademicPeriod = (item: AcademicPeriod) => {
    setActive(item.id, 'delete');

    Swal.fire({
        title: '¿Eliminar periodo académico?',
        html: `El periodo <strong>${item.code} — ${item.name}</strong> será eliminado permanentemente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#087ab1',
        cancelButtonColor: '#6b7280',
        focusCancel: true,
    }).then((result) => {
        if (!result.isConfirmed) {
            resetActive();
            return;
        }

        Swal.fire({
            title: '¿Estás completamente seguro?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, confirmar eliminación',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            focusCancel: true,
        }).then((second) => {
            if (!second.isConfirmed) {
                resetActive();
                return;
            }

            router.delete(route('admin.academic-periods.destroy', item.id), {
                preserveScroll: true,
                onFinish: () => resetActive(),
            });
        });
    });
};

const goToPage = (url: string | null) => {
    if (!url) return;
    router.visit(url, { preserveScroll: true, preserveState: true });
};
</script>

<template>
    <AppLayout>
        <Head title="Periodos Académicos" />

        <div class="space-y-6 px-6 py-6">
            <!-- HEADER -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#087ab1] shadow-md">
                        <CalendarDays class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Periodos Académicos</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ academicPeriods.total ?? academicPeriods.data.length }} periodo{{ (academicPeriods.total ?? academicPeriods.data.length) !== 1 ? 's' : '' }} registrado{{ (academicPeriods.total ?? academicPeriods.data.length) !== 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>
                <button
                    @click="openCreate"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-4 py-2.5 text-sm font-medium text-white shadow-md transition hover:from-[#066a98] hover:to-[#4fbdf5] active:scale-95"
                >
                    <CalendarDays class="h-4 w-4" /> Nuevo Periodo
                </button>
            </div>

            <!-- TABLA -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-linear-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Estado</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Código</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Nombre</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Fecha Inicio</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Fecha Fin</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Duración</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        <tr
                            v-for="item in academicPeriods.data"
                            :key="item.id"
                            class="group transition-all duration-150 hover:bg-[#68c8fb]/2 hover:shadow-[inset_3px_0_0_#087ab1] dark:hover:bg-[#68c8fb]/10 dark:hover:shadow-[inset_3px_0_0_#68c8fb]"
                        >
                            <!-- Estado -->
                            <td class="px-5 py-3.5">
                                <span
                                    v-if="item.status === 'active'"
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
                                    Cerrado
                                </span>
                            </td>

                            <!-- Código -->
                            <td class="px-5 py-3.5">
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ item.code }}</span>
                            </td>

                            <!-- Nombre -->
                            <td class="px-5 py-3.5">
                                <span class="text-sm text-gray-600 dark:text-gray-300">{{ item.name }}</span>
                            </td>

                            <!-- Fecha Inicio -->
                            <td class="px-5 py-3.5">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(item.start_date) }}</span>
                            </td>

                            <!-- Fecha Fin -->
                            <td class="px-5 py-3.5">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(item.end_date) }}</span>
                            </td>

                            <!-- Duración -->
                            <td class="px-5 py-3.5">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ calcDays(item.start_date, item.end_date) }} días</span>
                            </td>

                            <!-- Acciones -->
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Editar solo si está activo -->
                                    <button
                                        v-if="item.status === 'active'"
                                        @click="openEdit(item)"
                                        title="Editar"
                                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="activeAction.id === item.id && activeAction.type === 'edit'
                                            ? 'bg-[#087ab1] text-white shadow-md'
                                            : 'text-[#087ab1] hover:bg-[#087ab1] hover:text-white'"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                    </button>

                                    <button
                                        @click="deleteAcademicPeriod(item)"
                                        title="Eliminar"
                                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="activeAction.id === item.id && activeAction.type === 'delete'
                                            ? 'bg-red-600 text-white shadow-md'
                                            : 'text-red-500 hover:bg-red-600 hover:text-white'"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="academicPeriods.data.length === 0">
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10 dark:bg-[#087ab1]/20">
                                        <CalendarDays class="h-7 w-7 text-[#087ab1]/60" />
                                    </div>
                                    <p class="text-sm font-medium text-gray-500">No hay periodos académicos registrados</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div v-if="academicPeriods.links && academicPeriods.links.length > 3" class="flex justify-end">
                <nav class="inline-flex gap-1">
                    <template v-for="link in academicPeriods.links" :key="link.label">
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

        <!-- MODAL -->
        <AcademicPeriodModal
            :show="showModal"
            :mode="modalMode"
            :academic-period="selectedAcademicPeriod"
            @close="showModal = false; resetActive();"
        />
    </AppLayout>
</template>
