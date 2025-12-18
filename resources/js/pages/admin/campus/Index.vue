<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

// ✅ Modal reutilizable
import CampusModal from '@/components/campus/CampusModal.vue';

interface Campus {
    id: number;
    name: string;
    code?: string;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    campus: {
        data: Campus[];
        links: PaginationLink[];
    };
}>();

// Modal
const showModal = ref(false);
const selectedCampus = ref<Campus | null>(null);

// Acciones UI
const activeAction = ref<{
    id: number | null;
    type: 'edit' | 'delete' | null;
}>({
    id: null,
    type: null,
});

const setActive = (id: number, type: 'edit' | 'delete') => {
    activeAction.value = { id, type };
};

// Crear
const openCreateModal = () => {
    selectedCampus.value = null;
    showModal.value = true;
};

// Editar
const openEditModal = (campus: Campus) => {
    selectedCampus.value = campus;
    showModal.value = true;
};

// 🗑️ Eliminar
const deleteCampus = (campus: Campus) => {
    if (!confirm(`¿Eliminar el campus "${campus.name}"?`)) return;

    router.delete(route('admin.campus.destroy', campus.id));
};
</script>

<template>
    <Head title="Campus" />

    <AppLayout>
        <div class="space-y-6 px-8 py-6">
            <!-- HEADER -->
            <div
                class="flex items-center justify-between border-b border-gray-200 pb-4"
            >
                <h1
                    class="text-2xl font-semibold text-gray-800 dark:text-gray-100"
                >
                    Campus
                </h1>

                <button
                    @click="openCreateModal"
                    class="inline-flex cursor-pointer items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 active:scale-95"
                >
                    + Nuevo Campus
                </button>
            </div>

            <!-- TABLA -->
            <div
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <table
                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th
                                class="px-6 py-2 text-xs font-semibold uppercase"
                            >
                                #
                            </th>
                            <th
                                class="px-6 py-2 text-xs font-semibold uppercase"
                            >
                                Nombre
                            </th>
                            <th
                                class="px-6 py-2 text-xs font-semibold uppercase"
                            >
                                Código
                            </th>
                            <th
                                class="px-6 py-2 text-right text-xs font-semibold uppercase"
                            >
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <tr
                            v-for="(item, index) in campus.data"
                            :key="item.id"
                            class="group transition-all duration-200 even:bg-gray-50/40 hover:bg-indigo-50/40 dark:even:bg-gray-800/40"
                        >
                            <td class="px-6 py-2 text-sm">{{ index + 1 }}</td>
                            <td class="px-6 py-2 text-sm font-medium">
                                {{ item.name }}
                            </td>
                            <td class="px-6 py-2 text-sm">
                                {{ item.code ?? '-' }}
                            </td>

                            <td class="px-6 py-2 text-right">
                                <div class="flex justify-end gap-2">
                                    <!-- EDITAR -->
                                    <button
                                        @click="
                                            setActive(item.id, 'edit');
                                            openEditModal(item);
                                        "
                                        class="relative flex h-9 w-9 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="[
                                            activeAction.id === item.id &&
                                            activeAction.type === 'edit'
                                                ? 'bg-indigo-600 text-white shadow-lg'
                                                : 'text-indigo-600 hover:bg-indigo-600 hover:text-white',
                                        ]"
                                    >
                                        <Pencil
                                            class="h-4 w-4 transition-transform duration-200"
                                            :class="{
                                                'scale-110 rotate-12':
                                                    activeAction.id ===
                                                        item.id &&
                                                    activeAction.type ===
                                                        'edit',
                                            }"
                                        />
                                    </button>

                                    <!-- ELIMINAR -->
                                    <button
                                        @click="
                                            setActive(item.id, 'delete');
                                            deleteCampus(item);
                                        "
                                        class="relative flex h-9 w-9 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="[
                                            activeAction.id === item.id &&
                                            activeAction.type === 'delete'
                                                ? 'bg-red-600 text-white shadow-lg'
                                                : 'text-red-600 hover:bg-red-600 hover:text-white',
                                        ]"
                                    >
                                        <Trash2
                                            class="h-4 w-4 transition-transform duration-200"
                                            :class="{
                                                'scale-110 rotate-12':
                                                    activeAction.id ===
                                                        item.id &&
                                                    activeAction.type ===
                                                        'delete',
                                            }"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="campus.data.length === 0">
                            <td
                                colspan="4"
                                class="px-6 py-8 text-center text-sm text-gray-500"
                            >
                                No hay campus registrados
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div class="flex justify-end">
                <nav class="inline-flex gap-1">
                    <Link
                        v-for="link in campus.links"
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

        <!-- MODAL CREATE / EDIT -->
        <CampusModal
            :show="showModal"
            :campus="selectedCampus"
            @close="showModal = false"
        />
    </AppLayout>
</template>
