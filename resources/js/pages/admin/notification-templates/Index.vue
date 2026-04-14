<script setup lang="ts">
import NotificationTemplateModal from '@/components/notifications/NotificationTemplateModal.vue';
import NotificationTemplatePreviewModal from '@/components/notifications/NotificationTemplatePreviewModal.vue';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Eye, FileText, Files, Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const Swal = useSwal();

const translateLabel = (label: string): string =>
    label.replace('Previous', 'Anterior').replace('Next', 'Siguiente');

const isPageNumber = (label: string): boolean =>
    /^\d+$/.test(label.replace(/&[^;]+;/g, '').trim());

interface Template {
    id: number;
    name: string;
    subject: string;
    body: string;
    is_active: boolean;
    created_at: string;
}

defineProps<{
    templates: any;
}>();

/**
 * Control modal crear/editar
 */
const showModal = ref(false);
const selectedTemplate = ref<Template | null>(null);

/**
 * Control modal vista previa
 */
const showPreviewModal = ref(false);
const previewTemplate = ref<Template | null>(null);

const openPreview = (template: Template) => {
    previewTemplate.value = template;
    showPreviewModal.value = true;
};

/**
 * Estado visual de botones activos
 */
const activeAction = ref<{
    id: number | null;
    type: 'edit' | 'delete' | 'preview' | null;
}>({
    id: null,
    type: null,
});

const setActive = (id: number, type: 'edit' | 'delete' | 'preview') => {
    activeAction.value = { id, type };
};

const resetActive = () => {
    setTimeout(() => {
        activeAction.value = { id: null, type: null };
    }, 400);
};

/**
 * Crear
 */
const openCreate = () => {
    selectedTemplate.value = null;
    showModal.value = true;
};

/**
 * Editar
 */
const openEdit = (template: Template) => {
    setActive(template.id, 'edit');
    selectedTemplate.value = template;
    showModal.value = true;
};

/**
 * Eliminar
 */
const deleteTemplate = (template: Template) => {
    setActive(template.id, 'delete');

    Swal.fire({
        title: '¿Eliminar plantilla?',
        html: `La plantilla <strong>${template.name}</strong> será eliminada permanentemente.`,
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

            router.delete(
                route('admin.notification-templates.destroy', template.id),
                {
                    preserveScroll: true,
                    onFinish: () => resetActive(),
                },
            );
        });
    });
};

const formatDate = (date: string): string =>
    new Date(date).toLocaleDateString('es-PE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

/**
 * Paginación
 */
const goToPage = (url: string | null) => {
    if (!url) return;
    router.visit(url, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Plantillas de Notificación" />

        <div class="space-y-6 px-6 py-6">
            <!-- HEADER -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#087ab1] shadow-md"
                    >
                        <Files class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h1
                            class="text-xl font-bold text-gray-900 dark:text-gray-100"
                        >
                            Plantillas de Notificación
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ templates.total ?? templates.data.length }}
                            plantilla{{
                                (templates.total ?? templates.data.length) !== 1
                                    ? 's'
                                    : ''
                            }}
                            registrada{{
                                (templates.total ?? templates.data.length) !== 1
                                    ? 's'
                                    : ''
                            }}
                        </p>
                    </div>
                </div>

                <button
                    @click="openCreate"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-4 py-2.5 text-sm font-medium text-white shadow-md transition hover:from-[#066a98] hover:to-[#4fbdf5] active:scale-95"
                >
                    <FileText class="h-4 w-4" />
                    Nueva Plantilla
                </button>
            </div>

            <!-- TABLA -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
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
                                Estado
                            </th>
                            <th
                                class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                            >
                                Nombre
                            </th>
                            <th
                                class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase md:table-cell dark:text-gray-400"
                            >
                                Asunto
                            </th>
                            <th
                                class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400"
                            >
                                Creado
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
                            v-for="template in templates.data"
                            :key="template.id"
                            class="group transition-all duration-150 hover:bg-[#68c8fb]/2 hover:shadow-[inset_3px_0_0_#087ab1] dark:hover:bg-[#68c8fb]/10 dark:hover:shadow-[inset_3px_0_0_#68c8fb]"
                        >
                            <!-- Estado -->
                            <td class="px-5 py-3.5">
                                <span
                                    v-if="template.is_active"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                                >
                                    <span
                                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                                    />
                                    Activa
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-gray-400"
                                    />
                                    Inactiva
                                </span>
                            </td>

                            <!-- Nombre -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#087ab1]/10"
                                    >
                                        <FileText
                                            class="h-4 w-4 text-[#087ab1]"
                                        />
                                    </div>
                                    <span
                                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                    >
                                        {{ template.name }}
                                    </span>
                                </div>
                            </td>

                            <!-- Asunto -->
                            <td class="hidden px-5 py-3.5 md:table-cell">
                                <span
                                    class="text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ template.subject }}
                                </span>
                            </td>

                            <!-- Fecha -->
                            <td class="hidden px-5 py-3.5 lg:table-cell">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(template.created_at) }}
                                </span>
                            </td>

                            <!-- Acciones -->
                            <td class="px-5 py-3.5 text-right">
                                <div
                                    class="flex items-center justify-end gap-1.5"
                                >
                                    <!-- VISTA PREVIA -->
                                    <button
                                        @click="
                                            setActive(template.id, 'preview');
                                            openPreview(template);
                                        "
                                        title="Vista previa del correo"
                                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="
                                            activeAction.id === template.id &&
                                            activeAction.type === 'preview'
                                                ? 'bg-violet-600 text-white shadow-md'
                                                : 'text-violet-500 hover:bg-violet-600 hover:text-white'
                                        "
                                    >
                                        <Eye class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- EDITAR -->
                                    <button
                                        @click="openEdit(template)"
                                        title="Editar plantilla"
                                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="
                                            activeAction.id === template.id &&
                                            activeAction.type === 'edit'
                                                ? 'bg-[#087ab1] text-white shadow-md'
                                                : 'text-[#087ab1] hover:bg-[#087ab1] hover:text-white'
                                        "
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- ELIMINAR -->
                                    <button
                                        @click="deleteTemplate(template)"
                                        title="Eliminar plantilla"
                                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="
                                            activeAction.id === template.id &&
                                            activeAction.type === 'delete'
                                                ? 'bg-red-600 text-white shadow-md'
                                                : 'text-red-500 hover:bg-red-600 hover:text-white'
                                        "
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Sin datos -->
                        <tr v-if="templates.data.length === 0">
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div
                                        class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10 dark:bg-[#087ab1]/20"
                                    >
                                        <FileText
                                            class="h-7 w-7 text-[#087ab1]/60"
                                        />
                                    </div>
                                    <p
                                        class="text-sm font-medium text-gray-500"
                                    >
                                        No hay plantillas registradas
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div
                v-if="templates.links.length > 3"
                class="flex items-center justify-between"
            >
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <template v-if="templates.from">
                        Mostrando
                        <span
                            class="font-medium text-gray-700 dark:text-gray-300"
                            >{{ templates.from }}</span
                        >
                        a
                        <span
                            class="font-medium text-gray-700 dark:text-gray-300"
                            >{{ templates.to }}</span
                        >
                        de
                        <span
                            class="font-medium text-gray-700 dark:text-gray-300"
                            >{{ templates.total }}</span
                        >
                        resultados
                    </template>
                    <template v-else>Sin resultados</template>
                </p>
                <nav class="inline-flex gap-1">
                    <template
                        v-for="link in templates.links"
                        :key="link.label"
                    >
                        <a
                            v-if="link.url"
                            :href="link.url"
                            @click.prevent="goToPage(link.url)"
                            class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-all"
                            :class="
                                link.active && isPageNumber(link.label)
                                    ? 'border-[#68c8fb] bg-[#68c8fb] text-white shadow-sm'
                                    : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'
                            "
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

        <!-- MODAL crear/editar -->
        <NotificationTemplateModal
            :show="showModal"
            :template="selectedTemplate"
            @close="
                showModal = false;
                resetActive();
            "
        />

        <!-- MODAL vista previa -->
        <NotificationTemplatePreviewModal
            :show="showPreviewModal"
            :template="previewTemplate"
            @close="
                showPreviewModal = false;
                resetActive();
            "
        />
    </AppLayout>
</template>
