<script setup lang="ts">
import AttachTemplateModal from '@/components/notifications/AttachTemplateModal.vue';
import NotificationBatchModal from '@/components/notifications/NotificationBatchModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Eye, Link as LinkIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';

/* =========================
   TYPES
========================= */
interface NotificationBatch {
    id: number;
    name: string;
    status: string;
    execution_date: string;
    notification_template_id?: number | null;
    academic_period?: { id: number; name: string };
    campus?: { id: number; name: string };
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

interface NotificationTemplate {
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
    templates: NotificationTemplate[];
    filters: {
        academic_period_id?: string;
        campus_id?: string;
        status?: string;
    };
}>();

/* =========================
   AUTO REFRESH (PROCESSING)
========================= */

const hasProcessing = () => {
    return (props.batches?.data ?? []).some((b) => b.status === 'processing');
};

/* =========================
   FILTERS
========================= */
const filters = ref({
    academic_period_id: props.filters?.academic_period_id ?? '',
    campus_id: props.filters?.campus_id ?? '',
    status: props.filters?.status ?? '',
});

const applyFilters = () => {
    router.get(route('admin.notification-batches.index'), filters.value, {
        preserveState: true,
    });
};

const clearFilters = () => {
    filters.value = {
        academic_period_id: '',
        campus_id: '',
        status: '',
    };

    router.get(
        route('admin.notification-batches.index'),
        {},
        {
            preserveState: true,
        },
    );
};

/* =========================
   STATUS TRANSLATION
========================= */
const translateStatus = (status: string) => {
    switch (status) {
        case 'draft':
            return 'Borrador';
        case 'active':
            return 'Activo';
        case 'processing':
            return 'Procesando';
        case 'completed':
            return 'Completado';
        case 'completed_with_errors':
            return 'Completado con errores';
        case 'failed':
            return 'Fallido';
        default:
            return status;
    }
};

const statusClasses = (status: string) => {
    switch (status) {
        case 'draft':
            return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
        case 'active':
            return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
        case 'processing':
            return 'bg-blue-50 text-blue-700 ring-1 ring-blue-200 animate-pulse';
        case 'completed':
            return 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300';
        case 'completed_with_errors':
            return 'bg-orange-50 text-orange-700 ring-1 ring-orange-300';
        case 'failed':
            return 'bg-rose-50 text-rose-700 ring-1 ring-rose-300';
        default:
            return 'bg-gray-50 text-gray-700 ring-1 ring-gray-200';
    }
};

/* =========================
   TEMPLATE COLORS
========================= */
const templateClasses = (hasTemplate: boolean) => {
    return hasTemplate
        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 font-medium'
        : 'bg-gray-50 text-gray-500 ring-1 ring-gray-200';
};

/* =========================
   DATE FORMAT
========================= */
const formatDateTime = (date: string) => {
    if (!date) return '-';

    return new Date(date).toLocaleString('es-PE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

let refreshInterval: any = null;

const startPolling = () => {
    if (refreshInterval) return;

    refreshInterval = setInterval(() => {
        router.reload({
            only: ['batches'],
            preserveScroll: true,
            preserveState: true,
        } as any);
    }, 3000);
};

const stopPolling = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
};

watch(
    () => props.batches?.data,
    () => {
        if (hasProcessing()) {
            startPolling();
        } else {
            stopPolling();
        }
    },
    { immediate: true },
);

/* =========================
   MODAL LOGIC (VER DETALLE)
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

/* =========================
   MODAL LOGIC (ENLAZAR PLANTILLA)
========================= */
const showAttachModal = ref(false);
const selectedBatchId = ref<number | null>(null);

const selectedTemplateId = ref<number | null>(null);

const openAttachTemplate = (batch: NotificationBatch) => {
    selectedBatchId.value = batch.id;
    selectedTemplateId.value = batch.notification_template_id ?? null;
    showAttachModal.value = true;
};

const sending = ref(false);

const sendNotifications = async () => {
    if (!selectedBatch.value?.id) return;

    try {
        sending.value = true;

        await router.post(
            route('admin.notification-batches.send', selectedBatch.value.id),
            {},
            {
                preserveScroll: true,
                preserveState: true,

                onSuccess: async () => {
                    const response = await axios.get(
                        route(
                            'admin.notification-batches.show',
                            selectedBatch.value.id,
                        ),
                    );

                    selectedBatch.value = response.data;
                },

                onFinish: () => {
                    sending.value = false;
                },
            },
        );
    } catch (error) {
        console.error('Error enviando notificaciones', error);
        sending.value = false;
    }
};
</script>

<template>
    <Head title="Lotes de Notificación" />

    <AppLayout>
        <div class="space-y-6 p-6">
            <!-- HEADER -->
            <div>
                <h1 class="text-xl font-semibold">Lotes de Notificación</h1>
                <p class="text-sm text-gray-500">
                    Gestión de los lotes generados en el sistema.
                </p>
            </div>

            <!-- FILTROS -->
            <div
                class="flex flex-wrap items-center gap-3 rounded-lg border bg-gray-50 p-4"
            >
                <select
                    v-model="filters.academic_period_id"
                    class="rounded border px-3 py-2 text-sm"
                >
                    <option value="">Periodo académico</option>
                    <option
                        v-for="p in academicPeriods"
                        :key="p.id"
                        :value="p.id"
                    >
                        {{ p.name }}
                    </option>
                </select>

                <select
                    v-model="filters.campus_id"
                    class="rounded border px-3 py-2 text-sm"
                >
                    <option value="">Campus</option>
                    <option v-for="c in campus" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>

                <select
                    v-model="filters.status"
                    class="rounded border px-3 py-2 text-sm"
                >
                    <option value="">Estado</option>
                    <option value="draft">Borrador</option>
                    <option value="active">Activo</option>
                    <option value="processing">Procesando</option>
                    <option value="completed">Completado</option>
                    <option value="completed_with_errors">
                        Completado con errores
                    </option>
                    <option value="failed">Fallido</option>
                </select>

                <button
                    @click="applyFilters"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white shadow transition hover:bg-indigo-700"
                >
                    Filtrar
                </button>

                <button
                    @click="clearFilters"
                    class="rounded-lg bg-gray-400 px-4 py-2 text-sm text-white shadow transition hover:bg-gray-500"
                >
                    Limpiar
                </button>
            </div>

            <!-- TABLA -->
            <div class="overflow-x-auto rounded-xl bg-white shadow">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3 text-left">#</th>
                            <th class="p-3 text-left">Nombre</th>
                            <th class="p-3 text-left">Periodo</th>
                            <th class="p-3 text-left">Campus</th>
                            <th class="p-3 text-left">Fecha ejecución</th>
                            <th class="p-3 text-left">Plantilla</th>
                            <th class="p-3 text-left">Estado</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(item, index) in props.batches?.data ?? []"
                            :key="item.id"
                            class="border-t transition hover:bg-gray-50"
                        >
                            <td class="p-3">{{ index + 1 }}</td>
                            <td class="p-3 font-medium">{{ item.name }}</td>
                            <td class="p-3">
                                {{ item.academic_period?.name ?? '-' }}
                            </td>
                            <td class="p-3">{{ item.campus?.name ?? '-' }}</td>

                            <td class="p-3 text-gray-500">
                                {{ formatDateTime(item.execution_date) }}
                            </td>

                            <td class="p-3">
                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="
                                        templateClasses(
                                            !!item.notification_template_id,
                                        )
                                    "
                                >
                                    {{
                                        item.notification_template_id
                                            ? 'Asignada'
                                            : 'Sin plantilla'
                                    }}
                                </span>
                            </td>

                            <td class="p-3">
                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="statusClasses(item.status)"
                                >
                                    {{ translateStatus(item.status) }}
                                </span>
                            </td>

                            <td class="p-3">
                                <div class="flex justify-center gap-2">
                                    <!-- VER -->
                                    <button
                                        @click="openBatch(item.id)"
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-indigo-600 transition hover:bg-indigo-600 hover:text-white"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </button>

                                    <!-- ENLAZAR PLANTILLA -->
                                    <button
                                        @click="openAttachTemplate(item)"
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-green-600 transition hover:bg-green-600 hover:text-white"
                                    >
                                        <LinkIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="(props.batches?.data ?? []).length === 0">
                            <td
                                colspan="8"
                                class="p-6 text-center text-gray-500"
                            >
                                No hay lotes generados
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL ORIGINAL -->
        <NotificationBatchModal
            :show="showModal"
            :batch="selectedBatch"
            @close="showModal = false"
            @paginate="paginateBatch"
            @send="sendNotifications"
        />

        <!-- MODAL NUEVO -->
        <AttachTemplateModal
            :show="showAttachModal"
            :batch-id="selectedBatchId"
            :current-template-id="selectedTemplateId"
            @close="showAttachModal = false"
        />
    </AppLayout>
</template>
