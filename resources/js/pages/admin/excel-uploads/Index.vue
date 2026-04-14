<script setup lang="ts">
import ExcelUploadModal from '@/components/excel-uploads/ExcelUploadModal.vue';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Download, FileSpreadsheet, Trash2, Upload } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const Swal = useSwal();

const translateLabel = (label: string): string =>
    label.replace('Previous', 'Anterior').replace('Next', 'Siguiente');

const isPageNumber = (label: string): boolean =>
    /^\d+$/.test(label.replace(/&[^;]+;/g, '').trim());

/* =========================
Interfaces
========================= */
interface Upload {
    id: number;
    status: string;
    academic_period: { name: string };
    campus: { name: string };
    import_batch?: {
        name: string;
        file_name: string;
        file_size: number | null;
        total_rows: number | null;
        failed_rows: number | null;
        imported_at: string;
        is_active: boolean;
        user?: { name: string };
    };
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
    uploads: {
        data: Upload[];
        links: PaginationLink[];
        total?: number;
        from?: number;
        to?: number;
    };
    activePeriod: { id: number; name: string } | null;
    campus: { id: number; name: string }[];
    filters: { campus_id: string | null };
    templateUrl: string;
}>();

/* =========================
Filtro campus
========================= */
const selectedCampus = ref(props.filters.campus_id ?? '');

watch(selectedCampus, (val) => {
    router.get(
        route('admin.excel-uploads.index'),
        { campus_id: val || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
});

/* =========================
Modal State
========================= */
const showModal = ref(false);

/* =========================
Active action state
========================= */
const activeDelete = ref<number | null>(null);

const resetActive = () => {
    setTimeout(() => {
        activeDelete.value = null;
    }, 400);
};

/* =========================
Actions
========================= */
const openCreateModal = () => (showModal.value = true);
const closeModal = () => (showModal.value = false);

const formatFileSize = (bytes?: number): string => {
    if (!bytes) return '-';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const formatDate = (date: string): string =>
    new Date(date).toLocaleDateString('es-PE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

const goToPage = (url: string | null) => {
    if (!url) return;
    router.visit(url, { preserveScroll: true, preserveState: true });
};

const deleteUpload = (upload: Upload) => {
    activeDelete.value = upload.id;

    Swal.fire({
        title: '¿Eliminar carga?',
        html: `El archivo <strong>${upload.import_batch?.file_name ?? 'seleccionado'}</strong> y su lote asociado serán eliminados permanentemente.`,
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

            router.delete(route('admin.excel-uploads.destroy', upload.id), {
                preserveScroll: true,
                onFinish: () => resetActive(),
            });
        });
    });
};
</script>

<template>
    <Head title="Importar Reporte" />

    <AppLayout>
        <div class="space-y-6 px-6 py-6">

            <!-- HEADER -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#087ab1] shadow-md">
                        <FileSpreadsheet class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Importar Reporte Excel
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ uploads.total ?? uploads.data.length }}
                            archivo{{ (uploads.total ?? uploads.data.length) !== 1 ? 's' : '' }}
                            importado{{ (uploads.total ?? uploads.data.length) !== 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Filtro campus -->
                    <select
                        v-model="selectedCampus"
                        class="rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-[#087ab1] focus:outline-none focus:ring-2 focus:ring-[#68c8fb]/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        :class="selectedCampus ? 'border-[#087ab1] ring-2 ring-[#68c8fb]/20' : ''"
                    >
                        <option value="">Todos los campus</option>
                        <option v-for="c in campus" :key="c.id" :value="String(c.id)">
                            {{ c.name }}
                        </option>
                    </select>
                    <a
                        :href="templateUrl"
                        title="Descargar plantilla Excel"
                        class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400 dark:hover:bg-emerald-900/40"
                    >
                        <Download class="h-4 w-4" />
                        Plantilla
                    </a>
                    <button
                        @click="openCreateModal"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-4 py-2.5 text-sm font-medium text-white shadow-md transition hover:from-[#066a98] hover:to-[#4fbdf5] active:scale-95"
                    >
                        <Upload class="h-4 w-4" />
                        Subir Excel
                    </button>
                </div>
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
                                Periodo
                            </th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase md:table-cell dark:text-gray-400">
                                Campus
                            </th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                Lote / Archivo
                            </th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase sm:table-cell dark:text-gray-400">
                                Importado por
                            </th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase md:table-cell dark:text-gray-400">
                                Tamaño
                            </th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400">
                                Filas
                            </th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400">
                                Errores
                            </th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase sm:table-cell dark:text-gray-400">
                                Fecha
                            </th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        <tr
                            v-for="item in uploads.data"
                            :key="item.id"
                            class="group transition-all duration-150 hover:bg-[#68c8fb]/2 hover:shadow-[inset_3px_0_0_#087ab1] dark:hover:bg-[#68c8fb]/10 dark:hover:shadow-[inset_3px_0_0_#68c8fb]"
                        >
                            <!-- Estado -->
                            <td class="px-5 py-3.5">
                                <span
                                    v-if="item.import_batch?.is_active"
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
                                    Histórico
                                </span>
                            </td>

                            <!-- Periodo -->
                            <td class="px-5 py-3.5">
                                <span class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ item.academic_period?.name ?? '-' }}
                                </span>
                            </td>

                            <!-- Campus -->
                            <td class="hidden px-5 py-3.5 md:table-cell">
                                <span class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ item.campus?.name ?? '-' }}
                                </span>
                            </td>

                            <!-- Lote / Archivo -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#087ab1]/10">
                                        <FileSpreadsheet class="h-4 w-4 text-[#087ab1]" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                            {{ item.import_batch?.name ?? 'Sin lote' }}
                                        </p>
                                        <p class="truncate max-w-[160px] text-xs text-gray-400 dark:text-gray-500">
                                            {{ item.import_batch?.file_name ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Importado por -->
                            <td class="hidden px-5 py-3.5 sm:table-cell">
                                <span class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ item.import_batch?.user?.name ?? '-' }}
                                </span>
                            </td>

                            <!-- Tamaño -->
                            <td class="hidden px-5 py-3.5 md:table-cell">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatFileSize(item.import_batch?.file_size) }}
                                </span>
                            </td>

                            <!-- Filas totales -->
                            <td class="hidden px-5 py-3.5 lg:table-cell">
                                <span
                                    v-if="item.import_batch?.total_rows != null"
                                    class="inline-flex items-center rounded-full bg-[#087ab1]/10 px-2.5 py-0.5 text-xs font-medium text-[#087ab1] dark:bg-[#087ab1]/20 dark:text-[#68c8fb]"
                                >
                                    {{ item.import_batch.total_rows }}
                                </span>
                                <span v-else class="text-sm text-gray-400">-</span>
                            </td>

                            <!-- Errores -->
                            <td class="hidden px-5 py-3.5 lg:table-cell">
                                <span
                                    v-if="item.import_batch?.failed_rows != null && item.import_batch.failed_rows > 0"
                                    class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-600 dark:bg-red-900/30 dark:text-red-400"
                                >
                                    {{ item.import_batch.failed_rows }}
                                </span>
                                <span
                                    v-else-if="item.import_batch?.failed_rows === 0"
                                    class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                                >
                                    0
                                </span>
                                <span v-else class="text-sm text-gray-400">-</span>
                            </td>

                            <!-- Fecha -->
                            <td class="hidden px-5 py-3.5 sm:table-cell">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ item.import_batch?.imported_at ? formatDate(item.import_batch.imported_at) : '-' }}
                                </span>
                            </td>

                            <!-- Acciones -->
                            <td class="px-5 py-3.5 text-right">
                                <button
                                    @click="deleteUpload(item)"
                                    title="Eliminar carga"
                                    class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                    :class="activeDelete === item.id
                                        ? 'bg-red-600 text-white shadow-md'
                                        : 'text-red-500 hover:bg-red-600 hover:text-white'"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </td>
                        </tr>

                        <!-- Sin datos -->
                        <tr v-if="uploads.data.length === 0">
                            <td colspan="10" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10 dark:bg-[#087ab1]/20">
                                        <FileSpreadsheet class="h-7 w-7 text-[#087ab1]/60" />
                                    </div>
                                    <p class="text-sm font-medium text-gray-500">
                                        No hay archivos importados aún
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div v-if="uploads.links && uploads.links.length > 3" class="flex justify-end">
                <nav class="inline-flex gap-1">
                    <template v-for="link in uploads.links" :key="link.label">
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
        <ExcelUploadModal
            :show="showModal"
            :activePeriod="activePeriod"
            :campus="campus"
            @close="closeModal"
            @success="closeModal"
        />
    </AppLayout>
</template>
