<script setup lang="ts">
import OfficeModal from '@/components/offices/OfficeModal.vue';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Building2, Download, ImageIcon, Pencil, Plus, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const Swal = useSwal();

interface Office {
    id: number;
    name: string;
    email: string;
    cc_email?: string | null;
    level: number;
    is_active: boolean;
    signature?: string | null;
    notification_batches_count?: number;
}

interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
}

const props = defineProps<{
    offices?: {
        data: Office[];
        links: any[];
    } & PaginationMeta;
}>();

const offices = computed(() => props.offices?.data ?? []);
const paginationLinks = computed(() => props.offices?.links ?? []);

const paginationMeta = computed<PaginationMeta | null>(() => {
    if (!props.offices) return null;
    const { current_page, from, last_page, path, per_page, to, total } =
        props.offices;
    return { current_page, from, last_page, path, per_page, to, total };
});

const showModal = ref(false);
const editingOffice = ref<Office | null>(null);

const previewSignature = ref<string | null>(null);

const initialForm = () => ({
    name: '',
    email: '',
    cc_email: '',
    level: 1,
    is_active: true,
    signature: null,
});

const form = useForm(initialForm());

const openCreate = () => {
    editingOffice.value = null;

    form.defaults(initialForm());
    form.reset();

    showModal.value = true;
};

const openEdit = (office: Office) => {
    editingOffice.value = office;

    form.defaults({
        name: office.name,
        email: office.email,
        cc_email: office.cc_email ?? '',
        level: office.level,
        is_active: office.is_active,
        signature: null,
    });

    form.reset();

    showModal.value = true;
};

const save = (modalForm: typeof form) => {
    if (editingOffice.value) {
        modalForm.put(route('admin.offices.update', editingOffice.value.id), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                showModal.value = false;
                resetActive();
            },
        });
    } else {
        modalForm.post(route('admin.offices.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                showModal.value = false;
                resetActive();
            },
        });
    }
};

const remove = (office: Office) => {
    setActive(office.id, 'delete');

    Swal.fire({
        title: '¿Eliminar oficina?',
        html: `La oficina <strong>${office.name}</strong> será eliminada permanentemente.`,
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

            router.delete(route('admin.offices.destroy', office.id), {
                preserveScroll: true,
                onFinish: () => resetActive(),
            });
        });
    });
};

const activeAction = ref<{ id: number | null; type: 'edit' | 'delete' | null }>({
    id: null,
    type: null,
});

const setActive = (id: number, type: 'edit' | 'delete') => {
    activeAction.value = { id, type };
};

const resetActive = () => {
    setTimeout(() => {
        activeAction.value = { id: null, type: null };
    }, 400);
};

const levelClasses = (level: number) => {
    if (level === 1) return 'bg-[#087ab1]/10 text-[#087ab1] ring-1 ring-[#087ab1]/20';
    if (level === 2) return 'bg-[#68c8fb]/15 text-[#087ab1] ring-1 ring-[#68c8fb]/30';
    return 'bg-gray-100 text-gray-600 ring-1 ring-gray-200';
};

const closeModal = () => {
    showModal.value = false;
    editingOffice.value = null;

    form.defaults(initialForm());
    form.reset();
    resetActive();
};

const translateLabel = (label: string): string =>
    label.replace('Previous', 'Anterior').replace('Next', 'Siguiente');

const isPageNumber = (label: string): boolean =>
    /^\d+$/.test(label.replace(/&[^;]+;/g, '').trim());
</script>

<template>
    <Head title="Oficinas" />

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
                        <Building2 class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h1
                            class="text-xl font-bold text-gray-900 dark:text-gray-100"
                        >
                            Oficinas
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ offices.length }}
                            oficina{{ offices.length !== 1 ? 's' : '' }}
                            registrada{{ offices.length !== 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>

                <button
                    @click="openCreate"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-4 py-2.5 text-sm font-medium text-white shadow-md transition hover:from-[#066a98] hover:to-[#4fbdf5] active:scale-95"
                >
                    <Plus class="h-4 w-4" />
                    Nueva Oficina
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
                                Firma
                            </th>
                            <th
                                class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                            >
                                Nombre
                            </th>
                            <th
                                class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400"
                            >
                                Correo
                            </th>
                            <th
                                class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase sm:table-cell dark:text-gray-400"
                            >
                                Nivel
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
                            v-for="office in offices"
                            :key="office.id"
                            class="group transition-all duration-150 hover:bg-[#68c8fb]/2 hover:shadow-[inset_3px_0_0_#087ab1] dark:hover:bg-[#68c8fb]/10 dark:hover:shadow-[inset_3px_0_0_#68c8fb]"
                        >
                            <!-- Firma -->
                            <td class="px-5 py-3.5 text-center">
                                <div class="flex justify-center">
                                    <img
                                        v-if="office.signature"
                                        :src="`/storage/${office.signature}`"
                                        alt="Firma"
                                        @click="
                                            previewSignature = `/storage/${office.signature}`
                                        "
                                        class="h-12 max-w-[120px] cursor-pointer rounded-md border border-gray-200 bg-white object-contain p-1 shadow-sm transition duration-200 hover:scale-150 hover:shadow-lg"
                                    />
                                    <span
                                        v-else
                                        class="text-xs text-gray-400 italic"
                                    >
                                        Sin firma
                                    </span>
                                </div>
                            </td>

                            <!-- Nombre -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#087ab1]/10"
                                    >
                                        <Building2
                                            class="h-4 w-4 text-[#087ab1]"
                                        />
                                    </div>
                                    <span
                                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                    >
                                        {{ office.name }}
                                    </span>
                                </div>
                            </td>

                            <!-- Correo -->
                            <td class="hidden px-5 py-3.5 lg:table-cell">
                                <div>
                                    <div
                                        class="text-sm text-gray-600 dark:text-gray-300"
                                    >
                                        {{ office.email }}
                                    </div>
                                    <div
                                        v-if="office.cc_email"
                                        class="text-xs text-gray-400"
                                    >
                                        CC: {{ office.cc_email }}
                                    </div>
                                </div>
                            </td>

                            <!-- Nivel -->
                            <td class="hidden px-5 py-3.5 sm:table-cell">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="levelClasses(office.level)"
                                >
                                    Nivel {{ office.level }}
                                </span>
                            </td>

                            <!-- Estado -->
                            <td class="px-5 py-3.5">
                                <span
                                    v-if="office.is_active"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                                >
                                    <span
                                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                                    />
                                    Activo
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-gray-400"
                                    />
                                    Inactivo
                                </span>
                            </td>

                            <!-- Acciones -->
                            <td class="px-5 py-3.5 text-right">
                                <div
                                    class="flex items-center justify-end gap-1.5"
                                >
                                    <!-- EDITAR -->
                                    <button
                                        @click="
                                            setActive(office.id, 'edit');
                                            openEdit(office);
                                        "
                                        title="Editar oficina"
                                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="
                                            activeAction.id === office.id &&
                                            activeAction.type === 'edit'
                                                ? 'bg-[#087ab1] text-white shadow-md'
                                                : 'text-[#087ab1] hover:bg-[#087ab1] hover:text-white'
                                        "
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- ELIMINAR -->
                                    <button
                                        @click="
                                            setActive(office.id, 'delete');
                                            remove(office);
                                        "
                                        title="Eliminar oficina"
                                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="
                                            activeAction.id === office.id &&
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
                        <tr v-if="offices.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div
                                        class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10 dark:bg-[#087ab1]/20"
                                    >
                                        <Building2
                                            class="h-7 w-7 text-[#087ab1]/60"
                                        />
                                    </div>
                                    <p
                                        class="text-sm font-medium text-gray-500"
                                    >
                                        No hay oficinas registradas
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- PAGINACIÓN -->
                <div
                    v-if="paginationMeta && paginationLinks.length > 3"
                    class="flex items-center justify-between border-t border-gray-100 px-6 py-4 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <template v-if="paginationMeta.from">
                            Mostrando
                            <span
                                class="font-medium text-gray-700 dark:text-gray-300"
                                >{{ paginationMeta.from }}</span
                            >
                            a
                            <span
                                class="font-medium text-gray-700 dark:text-gray-300"
                                >{{ paginationMeta.to }}</span
                            >
                            de
                            <span
                                class="font-medium text-gray-700 dark:text-gray-300"
                                >{{ paginationMeta.total }}</span
                            >
                            oficinas
                        </template>
                        <template v-else>Sin resultados</template>
                    </p>
                    <nav class="inline-flex gap-1">
                        <template
                            v-for="link in paginationLinks"
                            :key="link.label"
                        >
                            <button
                                v-if="link.url"
                                :disabled="!link.url"
                                @click="
                                    link.url &&
                                        router.visit(link.url, {
                                            preserveScroll: true,
                                        })
                                "
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
        </div>

        <!-- MODAL -->
        <OfficeModal
            :key="editingOffice?.id ?? 'create'"
            :show="showModal"
            :editing="!!editingOffice"
            :form="form"
            :signature-path="editingOffice?.signature ?? null"
            @close="closeModal"
            @submit="save"
        />

        <!-- VISOR DE FIRMA -->
        <div
            v-if="previewSignature"
            @click.self="previewSignature = null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 transition"
        >
            <div
                class="relative w-[90vw] max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
            >
                <!-- HEADER -->
                <div
                    class="relative overflow-hidden bg-linear-to-br from-[#087ab1] to-[#68c8fb] px-6 py-5"
                >
                    <div
                        class="pointer-events-none absolute -top-10 -right-10 h-40 w-40 rounded-full bg-white/8"
                    />
                    <div class="relative flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25"
                            >
                                <ImageIcon class="h-4 w-4 text-white" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">
                                    Vista de Firma
                                </h3>
                                <p class="text-xs text-[#68c8fb]">
                                    PNG o JPG disponible para descarga
                                </p>
                            </div>
                        </div>
                        <button
                            @click="previewSignature = null"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 text-white transition hover:bg-white/25"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- IMAGEN -->
                <div class="flex justify-center p-6">
                    <img
                        :src="previewSignature"
                        class="max-h-[55vh] max-w-full rounded-xl border border-gray-200 bg-white object-contain p-2 shadow-sm"
                    />
                </div>

                <!-- FOOTER -->
                <div
                    class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/70 px-6 py-4"
                >
                    <a
                        :href="previewSignature"
                        download="firma.png"
                        class="flex items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-4 py-2 text-sm font-medium text-white shadow-md transition hover:from-[#066a98] hover:to-[#4fbdf5]"
                    >
                        <Download class="h-4 w-4" />
                        PNG
                    </a>
                    <a
                        :href="previewSignature"
                        download="firma.jpg"
                        class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:border-gray-300 hover:bg-gray-50"
                    >
                        <ImageIcon class="h-4 w-4" />
                        JPG
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
