<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { useSwal } from '@/composables/useSwal';

// 🔹 Modal
import FacultyModal from '@/components/faculties/FacultyModal.vue';

const Swal = useSwal();

/* =========================
   Interfaces
========================= */
interface Faculty {
    id: number;
    name: string;
    code: string;
    created_at: string;
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
    faculties: {
        data: Faculty[];
        links: PaginationLink[];
    };
}>();

/* =========================
   Estado UI
========================= */
const activeAction = ref<{
    id: number | null;
    type: 'edit' | 'delete' | null;
}>({
    id: null,
    type: null,
});

/* =========================
   Modal State
========================= */
const showModal = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const selectedFaculty = ref<Faculty | null>(null);

/* =========================
   Acciones
========================= */
const openCreateModal = () => {
    modalMode.value = 'create';
    selectedFaculty.value = null;
    showModal.value = true;
};

const openEditModal = (faculty: Faculty) => {
    modalMode.value = 'edit';
    selectedFaculty.value = faculty;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

/* =========================
   Eliminar Facultad
========================= */
const deleteFaculty = (faculty: Faculty) => {
    Swal.fire({
        title: '¿Eliminar facultad?',
        text: `La facultad "${faculty.name}" será eliminada permanentemente`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusCancel: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.faculties.destroy', faculty.id));
        }
    });
};
</script>

<template>
    <Head title="Facultades" />

    <AppLayout>
        <div class="space-y-6 px-8 py-6">
            <!-- HEADER -->
            <div class="flex items-center justify-between border-b border-gray-200 pb-4">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
                    Facultades
                </h1>

                <!-- CREAR -->
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 active:scale-95"
                >
                    + Nueva Facultad
                </button>
            </div>

            <!-- TABLA -->
            <div
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-2 text-xs font-semibold uppercase">#</th>
                            <th class="px-6 py-2 text-xs font-semibold uppercase">
                                Nombre
                            </th>
                            <th class="px-6 py-2 text-xs font-semibold uppercase">
                                Código
                            </th>
                            <th
                                class="px-6 py-2 text-right text-xs font-semibold uppercase"
                            >
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="(item, index) in faculties.data"
                            :key="item.id"
                            class="group transition-all duration-200 even:bg-gray-50/40 hover:bg-indigo-50/40 dark:even:bg-gray-800/40"
                        >
                            <td class="px-6 py-2 text-sm">
                                {{ index + 1 }}
                            </td>

                            <td class="px-6 py-2 text-sm font-medium">
                                {{ item.name }}
                            </td>

                            <td class="px-6 py-2 text-sm">
                                {{ item.code }}
                            </td>

                            <td class="px-6 py-2 text-right">
                                <div class="flex justify-end gap-2">
                                    <!-- EDITAR -->
                                    <button
                                        @click="openEditModal(item)"
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-indigo-600 transition hover:bg-indigo-600 hover:text-white"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>

                                    <!-- ELIMINAR -->
                                    <button
                                        @click="deleteFaculty(item)"
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-red-600 transition hover:bg-red-600 hover:text-white"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="faculties.data.length === 0">
                            <td
                                colspan="4"
                                class="px-6 py-8 text-center text-sm text-gray-500"
                            >
                                No hay facultades registradas
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div class="flex justify-end">
                <nav class="inline-flex gap-1">
                    <Link
                        v-for="link in faculties.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        class="rounded-md border px-3 py-1 text-sm transition"
                        :class="{
                            'border-indigo-600 bg-indigo-600 text-white':
                                link.active,
                            'border-gray-300 hover:bg-gray-100':
                                !link.active && link.url,
                            'cursor-not-allowed border-gray-200 text-gray-400':
                                !link.url,
                        }"
                    />
                </nav>
            </div>
        </div>

        <!-- MODAL -->
        <FacultyModal
            :show="showModal"
            :mode="modalMode"
            :faculty="selectedFaculty"
            @close="closeModal"
            @success="closeModal"
        />
    </AppLayout>
</template>
