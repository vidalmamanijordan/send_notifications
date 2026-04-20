<script setup lang="ts">
import AttachTemplateModal from '@/components/notifications/AttachTemplateModal.vue';
import EmailPreviewModal from '@/components/notifications/EmailPreviewModal.vue';
import NotificationBatchModal from '@/components/notifications/NotificationBatchModal.vue';
import OfficeAssignModal from '@/components/notifications/OfficeAssignModal.vue';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    AlertTriangle,
    Bell,
    Building2,
    CalendarDays,
    Eraser,
    Eye,
    FileText,
    Filter,
    LayoutList,
    Mail,
    Tag,
    Trash2,
    Wand2,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const Swal = useSwal();

/* =========================
   TYPES
========================= */
interface NotificationBatch {
    id: number;
    name: string;
    type: string;
    status: string;
    execution_date: string;
    notification_template_id?: number | null;
    office_id?: number | null;
    office?: { id: number; name: string; email: string; signature?: string };
    academic_period?: { id: number; name: string };
    campus?: { id: number; name: string };
    details_count?: number;
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
    kpiSmallBatches: number;
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
const PREVIEWED_KEY = 'previewed_batch_ids';
const storedPreviewed = JSON.parse(localStorage.getItem(PREVIEWED_KEY) ?? '[]') as number[];
const previewedBatchIds = ref<Set<number>>(new Set(storedPreviewed));

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
        const updated = new Set([...previewedBatchIds.value, item.id]);
        previewedBatchIds.value = updated;
        localStorage.setItem(PREVIEWED_KEY, JSON.stringify([...updated]));

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

/* =========================
   DELETE BATCH
========================= */
const deleteBatch = (item: NotificationBatch) => {
    Swal.fire({
        title: '¿Eliminar lote?',
        html: `Se eliminará el lote <strong>${item.name}</strong>. Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        focusCancel: true,
    }).then((result) => {
        if (!result.isConfirmed) return;
        router.delete(route('admin.notification-batches.destroy', item.id), {
            preserveScroll: true,
        });
    });
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

            <!-- FILTROS + ADVERTENCIA KPI -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start">

                <!-- FILTROS -->
                <div class="group relative flex-1 overflow-hidden rounded-2xl border border-sky-100 bg-linear-to-br from-sky-50 to-white p-5 shadow-sm transition hover:shadow-md dark:border-sky-900/30 dark:from-sky-900/20 dark:to-gray-800">

                    <!-- Cabecera -->
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-100 shadow-inner dark:bg-sky-800/40">
                                <Filter class="h-5 w-5 text-[#087ab1] dark:text-sky-400" />
                            </div>
                            <div>
                                <p class="text-xs font-medium tracking-wide text-[#087ab1] uppercase dark:text-sky-400">
                                    Filtrar lotes
                                </p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    {{ hasActiveFilters ? 'Filtros aplicados' : 'Todos los lotes' }}
                                </p>
                            </div>
                        </div>

                        <!-- Badge activo + botones -->
                        <div class="flex items-center gap-2">
                            <span
                                v-if="hasActiveFilters"
                                class="inline-flex items-center gap-1 rounded-full bg-[#087ab1] px-2.5 py-1 text-xs font-semibold text-white shadow-sm"
                            >
                                <Filter class="h-3 w-3" />
                                Activo
                            </span>
                            <button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                class="inline-flex cursor-pointer items-center gap-1 rounded-full border border-sky-200 bg-white px-2.5 py-1 text-xs font-medium text-sky-700 shadow-sm transition hover:bg-sky-50 active:scale-95 dark:border-sky-700 dark:bg-gray-800 dark:text-sky-300 dark:hover:bg-sky-900/30"
                            >
                                <X class="h-3 w-3" />
                                Limpiar
                            </button>
                            <button
                                @click="applyFilters"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-[#087ab1] px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[#066a98] active:scale-95"
                            >
                                <Filter class="h-3 w-3" />
                                Aplicar
                            </button>
                        </div>
                    </div>

                    <!-- Selectores -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                        <!-- Periodo académico -->
                        <div>
                            <label class="mb-1.5 flex items-center gap-1.5 text-xs font-medium tracking-wide text-[#087ab1] uppercase dark:text-sky-400">
                                <CalendarDays class="h-3.5 w-3.5" />
                                Periodo académico
                            </label>
                            <div class="relative">
                                <select
                                    v-model="filters.academic_period_id"
                                    class="w-full appearance-none rounded-xl border py-2.5 pl-4 pr-9 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#087ab1]/30"
                                    :class="filters.academic_period_id
                                        ? 'border-[#087ab1] bg-[#087ab1]/5 text-[#087ab1] dark:border-[#087ab1]/60 dark:bg-[#087ab1]/10 dark:text-sky-300'
                                        : 'border-sky-100 bg-white text-gray-700 hover:border-sky-200 dark:border-sky-800/50 dark:bg-gray-800 dark:text-gray-300'"
                                >
                                    <option value="">Todos los periodos</option>
                                    <option v-for="p in academicPeriods" :key="p.id" :value="p.id">
                                        {{ p.name }}
                                    </option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                                    <svg class="h-4 w-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Campus -->
                        <div>
                            <label class="mb-1.5 flex items-center gap-1.5 text-xs font-medium tracking-wide text-[#087ab1] uppercase dark:text-sky-400">
                                <Building2 class="h-3.5 w-3.5" />
                                Campus
                            </label>
                            <div class="relative">
                                <select
                                    v-model="filters.campus_id"
                                    class="w-full appearance-none rounded-xl border py-2.5 pl-4 pr-9 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#087ab1]/30"
                                    :class="filters.campus_id
                                        ? 'border-[#087ab1] bg-[#087ab1]/5 text-[#087ab1] dark:border-[#087ab1]/60 dark:bg-[#087ab1]/10 dark:text-sky-300'
                                        : 'border-sky-100 bg-white text-gray-700 hover:border-sky-200 dark:border-sky-800/50 dark:bg-gray-800 dark:text-gray-300'"
                                >
                                    <option value="">Todos los campus</option>
                                    <option v-for="c in campus" :key="c.id" :value="c.id">
                                        {{ c.name }}
                                    </option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                                    <svg class="h-4 w-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div>
                            <label class="mb-1.5 flex items-center gap-1.5 text-xs font-medium tracking-wide text-[#087ab1] uppercase dark:text-sky-400">
                                <Tag class="h-3.5 w-3.5" />
                                Estado
                            </label>
                            <div class="relative">
                                <select
                                    v-model="filters.status"
                                    class="w-full appearance-none rounded-xl border py-2.5 pl-4 pr-9 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#087ab1]/30"
                                    :class="filters.status
                                        ? 'border-[#087ab1] bg-[#087ab1]/5 text-[#087ab1] dark:border-[#087ab1]/60 dark:bg-[#087ab1]/10 dark:text-sky-300'
                                        : 'border-sky-100 bg-white text-gray-700 hover:border-sky-200 dark:border-sky-800/50 dark:bg-gray-800 dark:text-gray-300'"
                                >
                                    <option value="">Todos los estados</option>
                                    <option value="draft">Borrador</option>
                                    <option value="active">Activo</option>
                                    <option value="processing">Procesando</option>
                                    <option value="completed">Completado</option>
                                    <option value="completed_with_errors">Completado con errores</option>
                                    <option value="failed">Fallido</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                                    <svg class="h-4 w-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Breadcrumb cuando hay filtros activos -->
                    <div v-if="hasActiveFilters" class="mt-4 flex flex-wrap items-center gap-1.5 border-t border-sky-100 pt-4 text-xs dark:border-sky-900/30">
                        <span class="text-gray-400 dark:text-gray-500">Mostrando:</span>
                        <span v-if="filters.academic_period_id" class="inline-flex items-center gap-1 rounded-full bg-[#087ab1]/10 px-2.5 py-0.5 font-semibold text-[#087ab1] dark:bg-[#087ab1]/20 dark:text-sky-300">
                            <CalendarDays class="h-3 w-3" />
                            {{ academicPeriods.find(p => String(p.id) === String(filters.academic_period_id))?.name ?? 'Periodo seleccionado' }}
                        </span>
                        <span v-if="filters.campus_id" class="inline-flex items-center gap-1 rounded-full bg-[#087ab1]/10 px-2.5 py-0.5 font-semibold text-[#087ab1] dark:bg-[#087ab1]/20 dark:text-sky-300">
                            <Building2 class="h-3 w-3" />
                            {{ campus.find(c => String(c.id) === String(filters.campus_id))?.name ?? 'Campus seleccionado' }}
                        </span>
                        <span v-if="filters.status" class="inline-flex items-center gap-1 rounded-full bg-[#087ab1]/10 px-2.5 py-0.5 font-semibold text-[#087ab1] dark:bg-[#087ab1]/20 dark:text-sky-300">
                            <Tag class="h-3 w-3" />
                            {{ translateStatus(filters.status) }}
                        </span>
                    </div>

                </div>
                <!-- fin FILTROS -->

                <!-- ADVERTENCIA KPI -->
                <div class="flex w-full shrink-0 flex-col gap-3 rounded-2xl border border-amber-200 bg-linear-to-br from-amber-50 to-white p-5 shadow-sm lg:w-72 dark:border-amber-700/50 dark:from-amber-950/30 dark:to-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 shadow-inner dark:bg-amber-900/40">
                            <AlertTriangle class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                        </div>
                        <span class="text-xs font-semibold tracking-wide text-amber-700 uppercase dark:text-amber-400">Advertencia</span>
                    </div>
                    <p class="text-xs leading-relaxed text-amber-800 dark:text-amber-300">
                        Cada lote de notificaciones admite un máximo de <strong>100 destinatarios</strong>. Si se supera este límite, el envío no será procesado.
                    </p>
                    <div class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-100/60 px-3 py-2 dark:border-amber-700/40 dark:bg-amber-900/20">
                        <LayoutList class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                        <span class="text-xs text-amber-700 dark:text-amber-300">Lotes dentro del límite:</span>
                        <span class="ml-auto text-base font-bold text-amber-800 dark:text-amber-200">{{ props.kpiSmallBatches }}</span>
                    </div>
                </div>
                <!-- fin ADVERTENCIA KPI -->

            </div>
            <!-- fin FILTROS + ADVERTENCIA KPI -->

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
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                            :class="item.type === 'free' ? 'bg-violet-100 dark:bg-violet-900/30' : 'bg-[#087ab1]/10'"
                                        >
                                            <Bell
                                                class="h-4 w-4"
                                                :class="item.type === 'free' ? 'text-violet-500' : 'text-[#087ab1]'"
                                            />
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <span
                                                class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                            >
                                                {{ item.name }}
                                            </span>
                                            <span
                                                v-if="item.type === 'free'"
                                                class="inline-flex w-fit items-center rounded-full bg-violet-100 px-1.5 py-0 text-xs font-medium text-violet-700 dark:bg-violet-900/40 dark:text-violet-300"
                                            >
                                                Libre
                                            </span>
                                        </div>
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

                                    <!-- LÍMITE SUPERADO: reemplaza las acciones principales -->
                                    <div
                                        v-if="(item.details_count ?? 0) > 100"
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <div class="flex items-center gap-1.5 rounded-xl border border-red-200 bg-red-50 px-2.5 py-1.5 dark:border-red-800/40 dark:bg-red-900/20">
                                            <AlertTriangle class="h-3.5 w-3.5 shrink-0 text-red-500 dark:text-red-400" />
                                            <span class="text-xs font-medium leading-tight text-red-600 dark:text-red-400">
                                                Límite superado<br>
                                                <span class="font-normal">{{ item.details_count }} destinatarios</span>
                                            </span>
                                        </div>
                                        <button
                                            v-if="['draft', 'active'].includes(item.status)"
                                            @click="deleteBatch(item)"
                                            title="Eliminar lote"
                                            class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-red-400 transition-all duration-200 hover:bg-red-500 hover:text-white"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>

                                    <!-- ACCIONES NORMALES -->
                                    <div
                                        v-else
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <!-- ENLAZAR PLANTILLA — solo si el lote es editable y hay oficinas configuradas -->
                                        <button
                                            v-if="['draft', 'active'].includes(item.status) && props.offices.length > 0"
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

                                        <!-- ASIGNAR OFICINA — editable + plantilla enlazada + hay oficinas disponibles -->
                                        <button
                                            v-if="['draft', 'active'].includes(item.status) && item.notification_template_id && props.offices.length > 0"
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

                                        <!-- PREVIEW EMAIL — plantilla + oficina asignadas -->
                                        <button
                                            v-if="item.notification_template_id && item.office_id"
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

                                        <!-- VER DETALLE — requiere preview (o ya fue enviado) -->
                                        <button
                                            v-if="item.notification_template_id && item.office_id && (previewedBatchIds.has(item.id) || ['processing', 'completed', 'completed_with_errors'].includes(item.status))"
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

                                        <!-- ELIMINAR — solo antes de enviar -->
                                        <button
                                            v-if="['draft', 'active'].includes(item.status)"
                                            @click="deleteBatch(item)"
                                            title="Eliminar lote"
                                            class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-red-400 transition-all duration-200 hover:bg-red-500 hover:text-white"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
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
            :sending="sending"
            :preview-viewed="selectedBatch ? previewedBatchIds.has(selectedBatch.id) : false"
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
