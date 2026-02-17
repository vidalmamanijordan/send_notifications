<script setup lang="ts">
import ExcelUploadModal from '@/components/excel-uploads/ExcelUploadModal.vue';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const Swal = useSwal();

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
defineProps<{
    uploads: {
        data: Upload[];
        links: PaginationLink[];
    };
    academicPeriods: { id: number; name: string }[];
    campus: { id: number; name: string }[];
}>();

/* =========================
Modal State
========================= */
const showModal = ref(false);

/* =========================
Actions
========================= */
const openCreateModal = () => (showModal.value = true);
const closeModal = () => (showModal.value = false);

const deleteUpload = (upload: Upload) => {
    Swal.fire({
        title: '¿Eliminar carga?',
        text: 'Se eliminará el archivo y su lote asociado',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.excel-uploads.destroy', upload.id));
        }
    });
};
</script>

<template>
    <Head title="Importar Reporte" />

    <AppLayout>
        <div class="space-y-6 px-8 py-6">

            <!-- HEADER -->
            <div class="flex items-center justify-between border-b pb-4">
                <h1 class="text-2xl font-semibold">
                    Importar Reporte Excel
                </h1>

                <button
                    @click="openCreateModal"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 active:scale-95"
                >
                    + Subir Excel
                </button>
            </div>

            <!-- TABLA -->
            <div class="rounded-lg border bg-white shadow-sm">
                <div class="overflow-x-auto scroll-smooth">
                    <table class="min-w-[1200px] w-full divide-y">

                        <!-- HEADER -->
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-3 text-xs uppercase whitespace-nowrap">#</th>
                                <th class="px-6 py-3 text-xs uppercase whitespace-nowrap">Periodo</th>
                                <th class="px-6 py-3 text-xs uppercase whitespace-nowrap">Campus</th>
                                <th class="px-6 py-3 text-xs uppercase whitespace-nowrap">Carga</th>
                                <th class="px-6 py-3 text-xs uppercase whitespace-nowrap">Archivo</th>
                                <th class="px-6 py-3 text-xs uppercase whitespace-nowrap">Importado por</th>
                                <th class="px-6 py-3 text-xs uppercase whitespace-nowrap">Fecha</th>
                                <th class="px-6 py-3 text-xs uppercase whitespace-nowrap">Activo</th>
                                <th class="px-6 py-3 text-xs uppercase whitespace-nowrap">Estado</th>
                                <th class="px-6 py-3 text-xs uppercase text-right whitespace-nowrap">Acciones</th>
                            </tr>
                        </thead>

                        <!-- BODY -->
                        <tbody class="divide-y">
                            <tr
                                v-for="(item, index) in uploads.data"
                                :key="item.id"
                                class="hover:bg-indigo-50/40"
                            >
                                <td class="px-6 py-2 text-sm whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <td class="px-6 py-2 text-sm whitespace-nowrap">
                                    {{ item.academic_period?.name }}
                                </td>

                                <td class="px-6 py-2 text-sm whitespace-nowrap">
                                    {{ item.campus?.name }}
                                </td>

                                <td class="px-6 py-2 text-sm font-medium whitespace-nowrap">
                                    {{ item.import_batch?.name ?? '-' }}
                                </td>

                                <td class="px-6 py-2 text-sm text-gray-500 whitespace-nowrap">
                                    <span class="truncate block max-w-[180px]">
                                        {{ item.import_batch?.file_name ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-6 py-2 text-sm whitespace-nowrap">
                                    {{ item.import_batch?.user?.name ?? '-' }}
                                </td>

                                <td class="px-6 py-2 text-sm text-gray-500 whitespace-nowrap">
                                    {{ item.import_batch?.imported_at ?? '-' }}
                                </td>

                                <td class="px-6 py-2 text-sm whitespace-nowrap">
                                    <span
                                        v-if="item.import_batch?.is_active"
                                        class="rounded bg-green-100 px-2 py-1 text-xs text-green-700"
                                    >
                                        Activo
                                    </span>
                                    <span
                                        v-else
                                        class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600"
                                    >
                                        Histórico
                                    </span>
                                </td>

                                <td class="px-6 py-2 text-sm capitalize whitespace-nowrap">
                                    {{ item.status }}
                                </td>

                                <td class="px-6 py-2 text-right whitespace-nowrap">
                                    <button
                                        @click="deleteUpload(item)"
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-red-600 hover:bg-red-600 hover:text-white"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="uploads.data.length === 0">
                                <td colspan="10" class="py-6 text-center text-sm text-gray-500">
                                    No hay archivos cargados
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PAGINACIÓN -->
            <div class="flex justify-end">
                <nav class="inline-flex gap-1">
                    <Link
                        v-for="link in uploads.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        class="rounded-md border px-3 py-1 text-sm"
                        :class="{
                            'bg-indigo-600 text-white': link.active,
                            'hover:bg-gray-100': !link.active && link.url,
                        }"
                    />
                </nav>
            </div>
        </div>

        <!-- MODAL -->
        <ExcelUploadModal
            :show="showModal"
            :academicPeriods="academicPeriods"
            :campus="campus"
            @close="closeModal"
            @success="closeModal"
        />
    </AppLayout>
</template>
