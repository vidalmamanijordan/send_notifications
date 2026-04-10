<script setup lang="ts">
import AttachTemplateModal from '@/components/notifications/AttachTemplateModal.vue';
import EmailPreviewModal from '@/components/notifications/EmailPreviewModal.vue';
import NotificationBatchModal from '@/components/notifications/NotificationBatchModal.vue';
import OfficeAssignModal from '@/components/notifications/OfficeAssignModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Bell,
    Building2,
    Eraser,
    Eye,
    FileText,
    Filter,
    Mail,
    Wand2,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

/* =========================
   TYPES
========================= */
interface NotificationBatch {
    id: number;
    name: string;
    status: string;
    execution_date: string;
    notification_template_id?: number | null;
    office_id?: number | null;
    office?: { id: number; name: string; email: string; signature?: string };
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

interface Office {
    id: number;
    name: string;
    email: string;
    signature?: string;
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
    offices: Office[];
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

const hasActiveFilters = computed(() =>
    !!filters.value.academic_period_id ||
    !!filters.value.campus_id ||
    !!filters.value.status,
);

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
const currentBatchPage = ref(1);

/* =========================
   MODAL PREVIEW EMAIL
========================= */
const showPreviewModal = ref(false);
const previewBatchId = ref<number | null>(null);
const previewSubject = ref('');
const previewHtml = ref('');
const previewEmails = ref<string[]>([]);
const previewTeachers = ref<any[]>([]);

watch(
    () => props.batches?.data,
    async (newBatches) => {
        if (!selectedBatch.value?.id) return;

        const updated = newBatches?.find(
            (b) => b.id === selectedBatch.value.id,
        );

        if (!updated) return;

        if (updated.status === 'processing') {
            selectedBatch.value.status = updated.status;
            return;
        }

        const response = await axios.get(
            route('admin.notification-batches.show', updated.id),
            {
                params: { page: currentBatchPage.value },
            },
        );

        selectedBatch.value = response.data;

        selectedBatch.value = response.data;
    },
);

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

const previewEmail = async (item: NotificationBatch) => {
    try {
        const response = await axios.get(
            route('admin.notification-batches.preview', item.id),
        );

        previewSubject.value = response.data.subject;
        previewHtml.value = response.data.html;
        previewEmails.value = response.data.emails ?? [];
        previewTeachers.value = response.data.teachers ?? [];
        previewBatchId.value = item.id;

        showPreviewModal.value = true;
    } catch (error) {
        console.error('Error cargando preview', error);
    }
};

const paginateBatch = async (url: string) => {
    if (!url) return;

    try {
        const page = new URL(url).searchParams.get('page');
        currentBatchPage.value = page ? Number(page) : 1;

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

/* =========================
   MODAL LOGIC (OFICINA)
========================= */

const showOfficeModal = ref(false);
const selectedOfficeBatch = ref<number | null>(null);
const selectedOfficeId = ref<number | null>(null);
const openOfficeModal = (batch: NotificationBatch) => {
    selectedOfficeBatch.value = batch.id;
    selectedOfficeId.value = (batch as any).office_id ?? null;

    router.reload({
        only: ['offices'],
        preserveScroll: true,
        preserveState: true,

        onSuccess: () => {
            showOfficeModal.value = true;
        },
    });
};

/* =========================
   SEND LOGIC
========================= */

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
                replace: true,

                onSuccess: async () => {
                    router.get(
                        route('admin.notification-batches.index'),
                        filters.value,
                        {
                            only: ['batches'],
                            preserveScroll: true,
                            preserveState: true,
                            replace: true,
                        },
                    );

                    const response = await axios.get(
                        route(
                            'admin.notification-batches.show',
                            selectedBatch.value.id,
                        ),
                        {
                            params: { page: currentBatchPage.value },
                        },
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

const resendNotification = async (detailId: number) => {
    if (!selectedBatch.value?.id) return;

    try {
        await router.post(
            route('admin.notification-batch-details.resend', detailId),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,

                onSuccess: async () => {
                    const response = await axios.get(
                        route(
                            'admin.notification-batches.show',
                            selectedBatch.value.id,
                        ),
                        {
                            params: { page: currentBatchPage.value },
                        },
                    );

                    selectedBatch.value = response.data;
                },
            },
        );
    } catch (error) {
        console.error('Error reenviando notificación', error);
    }
};
</script>

<template>
    <Head title="Lotes de Notificación" />

    <AppLayout>
        <div class="space-y-6 px-6 py-6">
            <!-- HEADER -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#087ab1] shadow-md"
                    >
                        <Bell class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h1
                            class="text-xl font-bold text-gray-900 dark:text-gray-100"
                        >
                            Lotes de Notificación
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ props.batches?.data.length ?? 0 }}
                            lote{{
                                (props.batches?.data.length ?? 0) !== 1
                                    ? 's'
                                    : ''
                            }}
                            generado{{
                                (props.batches?.data.length ?? 0) !== 1
                                    ? 's'
                                    : ''
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- FILTROS -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <div
                    class="flex items-center gap-2 border-b border-gray-100 bg-linear-to-r from-gray-50 to-gray-100 px-5 py-3 dark:border-gray-700 dark:from-gray-800 dark:to-gray-800"
                >
                    <Filter class="h-3.5 w-3.5 text-[#087ab1]" />
                    <span
                        class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                        >Filtros</span
                    >
                </div>

                <div class="flex flex-wrap items-center gap-3 px-5 py-4">
                    <select
                        v-model="filters.academic_period_id"
                        class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 transition focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
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
                        class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 transition focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                        <option value="">Campus</option>
                        <option v-for="c in campus" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>

                    <select
                        v-model="filters.status"
                        class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 transition focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
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
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-4 py-2 text-sm font-medium text-white shadow-md transition hover:from-[#066a98] hover:to-[#4fbdf5] active:scale-95"
                    >
                        <Filter class="h-3.5 w-3.5" />
                        Filtrar
                    </button>

                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-500 active:scale-95"
                    >
                        <Eraser class="h-3.5 w-3.5" />
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- TABLA -->
            <div
                class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="overflow-x-auto">
                    <table
                        class="min-w-full divide-y divide-gray-100 dark:divide-gray-700"
                    >
                        <thead>
                            <tr
                                class="bg-linear-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800"
                            >
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                                >
                                    #
                                </th>
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Nombre
                                </th>
                                <th
                                    class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase md:table-cell dark:text-gray-400"
                                >
                                    Periodo
                                </th>
                                <th
                                    class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400"
                                >
                                    Campus
                                </th>
                                <th
                                    class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase xl:table-cell dark:text-gray-400"
                                >
                                    Fecha ejecución
                                </th>
                                <th
                                    class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase sm:table-cell dark:text-gray-400"
                                >
                                    Plantilla
                                </th>
                                <th
                                    class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase md:table-cell dark:text-gray-400"
                                >
                                    Oficina
                                </th>
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Estado
                                </th>
                                <th
                                    class="px-5 py-3.5 text-right text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Acciones
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="(item, index) in props.batches?.data ??
                                []"
                                :key="item.id"
                                class="group transition-all duration-150 hover:bg-[#68c8fb]/2 hover:shadow-[inset_3px_0_0_#087ab1] dark:hover:bg-[#68c8fb]/10 dark:hover:shadow-[inset_3px_0_0_#68c8fb]"
                            >
                                <!-- # -->
                                <td
                                    class="px-5 py-3.5 text-sm text-gray-400 dark:text-gray-500"
                                >
                                    {{ index + 1 }}
                                </td>

                                <!-- Nombre -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#087ab1]/10"
                                        >
                                            <Bell
                                                class="h-4 w-4 text-[#087ab1]"
                                            />
                                        </div>
                                        <span
                                            class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                        >
                                            {{ item.name }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Periodo -->
                                <td class="hidden px-5 py-3.5 md:table-cell">
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-300"
                                    >
                                        {{ item.academic_period?.name ?? '-' }}
                                    </span>
                                </td>

                                <!-- Campus -->
                                <td class="hidden px-5 py-3.5 lg:table-cell">
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-300"
                                    >
                                        {{ item.campus?.name ?? '-' }}
                                    </span>
                                </td>

                                <!-- Fecha -->
                                <td class="hidden px-5 py-3.5 xl:table-cell">
                                    <span
                                        class="text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        {{
                                            formatDateTime(item.execution_date)
                                        }}
                                    </span>
                                </td>

                                <!-- Plantilla -->
                                <td class="hidden px-5 py-3.5 sm:table-cell">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="
                                            templateClasses(
                                                !!item.notification_template_id,
                                            )
                                        "
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                item.notification_template_id
                                                    ? 'bg-emerald-500'
                                                    : 'bg-gray-400'
                                            "
                                        />
                                        {{
                                            item.notification_template_id
                                                ? 'Asignada'
                                                : 'Sin plantilla'
                                        }}
                                    </span>
                                </td>

                                <!-- Oficina -->
                                <td class="hidden px-5 py-3.5 md:table-cell">
                                    <div
                                        v-if="item.office"
                                        class="flex items-center gap-2.5"
                                    >
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#087ab1]/10"
                                        >
                                            <Building2
                                                class="h-4 w-4 text-[#087ab1]"
                                            />
                                        </div>
                                        <div
                                            class="flex flex-col leading-tight"
                                        >
                                            <span
                                                class="text-sm font-medium text-gray-800 dark:text-gray-100"
                                            >
                                                {{ item.office.name }}
                                            </span>
                                            <span
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {{ item.office.email }}
                                            </span>
                                        </div>
                                    </div>
                                    <div
                                        v-else
                                        class="flex items-center gap-1.5 text-gray-400"
                                    >
                                        <Building2 class="h-3.5 w-3.5" />
                                        <span class="text-xs italic">
                                            Sin oficina
                                        </span>
                                    </div>
                                </td>

                                <!-- Estado -->
                                <td class="px-5 py-3.5">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                        :class="statusClasses(item.status)"
                                    >
                                        {{ translateStatus(item.status) }}
                                    </span>
                                </td>

                                <!-- Acciones -->
                                <td class="px-5 py-3.5 text-right">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <!-- ENLAZAR PLANTILLA -->
                                        <button
                                            @click="openAttachTemplate(item)"
                                            title="Enlazar plantilla"
                                            :class="[
                                                'flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-white transition-all duration-200',
                                                showAttachModal && selectedBatchId === item.id
                                                    ? 'scale-110 bg-red-500 ring-2 ring-red-400/50 ring-offset-1'
                                                    : 'bg-red-400/80 hover:bg-red-500',
                                            ]"
                                        >
                                            <FileText
                                                class="h-3.5 w-3.5"
                                                :class="showAttachModal && selectedBatchId === item.id ? 'animate-spin' : ''"
                                            />
                                        </button>

                                        <!-- ASIGNAR OFICINA -->
                                        <button
                                            @click="openOfficeModal(item)"
                                            title="Asignar oficina"
                                            :class="[
                                                'flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-white transition-all duration-200',
                                                showOfficeModal && selectedOfficeBatch === item.id
                                                    ? 'scale-110 bg-yellow-500 ring-2 ring-yellow-400/50 ring-offset-1'
                                                    : 'bg-yellow-400/80 hover:bg-yellow-500',
                                            ]"
                                        >
                                            <Mail
                                                class="h-3.5 w-3.5"
                                                :class="showOfficeModal && selectedOfficeBatch === item.id ? 'animate-spin' : ''"
                                            />
                                        </button>

                                        <!-- PREVIEW EMAIL -->
                                        <button
                                            @click="previewEmail(item)"
                                            title="Vista previa del correo"
                                            :class="[
                                                'flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-white transition-all duration-200',
                                                showPreviewModal && previewBatchId === item.id
                                                    ? 'scale-110 bg-green-500 ring-2 ring-green-400/50 ring-offset-1'
                                                    : 'bg-green-400/80 hover:bg-green-500',
                                            ]"
                                        >
                                            <Eye
                                                class="h-3.5 w-3.5"
                                                :class="showPreviewModal && previewBatchId === item.id ? 'animate-spin' : ''"
                                            />
                                        </button>

                                        <!-- ORQUESTADOR -->
                                        <button
                                            @click="openBatch(item.id)"
                                            title="Ver detalle del lote"
                                            :class="[
                                                'flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-white transition-all duration-200',
                                                showModal && selectedBatch?.id === item.id
                                                    ? 'scale-110 bg-[#087ab1] ring-2 ring-[#087ab1]/50 ring-offset-1'
                                                    : 'bg-[#087ab1]/80 hover:bg-[#087ab1]',
                                            ]"
                                        >
                                            <Wand2
                                                class="h-3.5 w-3.5"
                                                :class="showModal && selectedBatch?.id === item.id ? 'animate-spin' : ''"
                                            />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Sin datos -->
                            <tr v-if="(props.batches?.data ?? []).length === 0">
                                <td colspan="9" class="px-6 py-16 text-center">
                                    <div
                                        class="flex flex-col items-center gap-3"
                                    >
                                        <div
                                            class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10 dark:bg-[#087ab1]/20"
                                        >
                                            <Bell
                                                class="h-7 w-7 text-[#087ab1]/60"
                                            />
                                        </div>
                                        <p
                                            class="text-sm font-medium text-gray-500"
                                        >
                                            No hay lotes generados
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL LOTE -->
        <NotificationBatchModal
            :show="showModal"
            :batch="selectedBatch"
            @close="showModal = false"
            @paginate="paginateBatch"
            @send="sendNotifications"
            @resend="resendNotification"
        />

        <!-- MODAL ENLAZAR PLANTILLA -->
        <AttachTemplateModal
            :show="showAttachModal"
            :batch-id="selectedBatchId"
            :current-template-id="selectedTemplateId"
            @close="showAttachModal = false"
        />

        <!-- MODAL OFICINA -->
        <OfficeAssignModal
            :show="showOfficeModal"
            :batch-id="selectedOfficeBatch"
            :offices="props.offices"
            :current-office-id="selectedOfficeId"
            @close="showOfficeModal = false"
        />

        <!-- MODAL EMAIL PREVIEW -->
        <EmailPreviewModal
            :show="showPreviewModal"
            :subject="previewSubject"
            :html="previewHtml"
            :emails="previewEmails"
            :teachers="previewTeachers"
            @close="showPreviewModal = false; previewBatchId = null"
        />
    </AppLayout>
</template>
