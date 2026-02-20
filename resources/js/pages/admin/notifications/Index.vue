<script setup lang="ts">
import NotificationBatchModal from '@/components/notifications/NotificationBatchModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Eye } from 'lucide-vue-next';
import { ref } from 'vue';

/* =========================
   TYPES
========================= */
interface NotificationBatch {
    id: number;
    name: string;
    status: string;
    execution_date: string;
    academic_period?: {
        id: number;
        name: string;
    };
    campus?: {
        id: number;
        name: string;
    };
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface AcademicPeriod {
    id: number;
    name: string;
}

interface Campus {
    id: number;
    name: string;
}

/* =========================
   PROPS
========================= */
const props = defineProps<{
    batches?: {
        data: NotificationBatch[];
        links: PaginationLink[];
    };
    academicPeriods: AcademicPeriod[];
    campus: Campus[];
}>();

/* =========================
   BUILD BATCH FORM
========================= */
const form = useForm({
    academic_period_id: '',
    campus_id: '',
});

const buildBatch = () => {
    if (!form.academic_period_id || !form.campus_id) {
        alert('Debe seleccionar periodo y campus');
        return;
    }

    form.post(route('admin.notification-batches.build'), {
        preserveScroll: true,
    });
};

/* =========================
   MODAL LOGIC
========================= */
const showModal = ref(false);
const selectedBatch = ref<any>(null);

const openBatch = async (id: number) => {
    try {
        const response = await axios.get(
            route('admin.notification-batches.show', id),
        );

        selectedBatch.value = response.data;
        showModal.value = true;
    } catch (error) {
        console.error('Error cargando lote', error);
    }
};

const paginateBatch = async (url: string) => {
    if (!url) return;

    try {
        const response = await axios.get(url);
        selectedBatch.value = response.data;
    } catch (error) {
        console.error('Error paginando lote', error);
    }
};
</script>

<template>
    <Head title="Lotes de Notificación" />

    <AppLayout>
        <div class="space-y-6 px-8 py-6">
            <!-- HEADER -->
            <div class="flex items-center justify-between border-b pb-4">
                <h1 class="text-2xl font-semibold">Lotes de Notificación</h1>

                <div class="flex items-center gap-2">
                    <!-- PERIODO -->
                    <select
                        v-model="form.academic_period_id"
                        class="rounded border px-3 py-2 text-sm"
                    >
                        <option value="">Periodo</option>
                        <option
                            v-for="p in academicPeriods"
                            :key="p.id"
                            :value="p.id"
                        >
                            {{ p.name }}
                        </option>
                    </select>

                    <!-- CAMPUS -->
                    <select
                        v-model="form.campus_id"
                        class="rounded border px-3 py-2 text-sm"
                    >
                        <option value="">Campus</option>
                        <option v-for="c in campus" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>

                    <!-- BOTÓN -->
                    <button
                        @click="buildBatch"
                        :disabled="form.processing"
                        class="rounded bg-indigo-600 px-4 py-2 text-white transition hover:bg-indigo-700"
                    >
                        {{
                            form.processing
                                ? 'Construyendo...'
                                : 'Construir Lote'
                        }}
                    </button>
                </div>
            </div>

            <!-- TABLA -->
            <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                <table class="min-w-full divide-y">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Nombre</th>
                            <th class="px-4 py-2">Periodo</th>
                            <th class="px-4 py-2">Campus</th>
                            <th class="px-4 py-2">Estado</th>
                            <th class="px-4 py-2 text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(item, index) in props.batches?.data ?? []"
                            :key="item.id"
                            class="border-t"
                        >
                            <td class="px-4 py-2">
                                {{ index + 1 }}
                            </td>

                            <td class="px-4 py-2 font-medium">
                                {{ item.name }}
                            </td>

                            <td class="px-4 py-2">
                                {{ item.academic_period?.name ?? '-' }}
                            </td>

                            <td class="px-4 py-2">
                                {{ item.campus?.name ?? '-' }}
                            </td>

                            <td class="px-4 py-2">
                                <span
                                    class="rounded-full px-2 py-1 text-xs"
                                    :class="
                                        item.status === 'active'
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-gray-100 text-gray-600'
                                    "
                                >
                                    {{ item.status }}
                                </span>
                            </td>

                            <td class="px-4 py-2 text-right">
                                <button
                                    @click="openBatch(item.id)"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full text-indigo-600 transition hover:bg-indigo-600 hover:text-white"
                                >
                                    <Eye class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>

                        <tr v-if="(props.batches?.data ?? []).length === 0">
                            <td
                                colspan="6"
                                class="py-6 text-center text-gray-400"
                            >
                                No hay lotes generados
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL -->
        <NotificationBatchModal
            :show="showModal"
            :batch="selectedBatch"
            @close="showModal = false"
            @paginate="paginateBatch"
        />
    </AppLayout>
</template>
