<script setup lang="ts">
import OfficeModal from '@/components/offices/OfficeModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Office {
    id: number;
    name: string;
    code: string;
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

const initialForm = () => ({
    name: '',
    code: '',
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
        code: office.code,
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
            onSuccess: () => (showModal.value = false),
        });
    } else {
        modalForm.post(route('admin.offices.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => (showModal.value = false),
        });
    }
};

const remove = (office: Office) => {
    if (!confirm('¿Seguro que deseas eliminar esta oficina?')) return;
    router.delete(route('admin.offices.destroy', office.id));
};

const statusClasses = (active: boolean) =>
    active
        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
        : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';

const levelClasses = (level: number) => {
    if (level === 1)
        return 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200';
    if (level === 2) return 'bg-blue-50 text-blue-700 ring-1 ring-blue-200';
    return 'bg-gray-50 text-gray-700 ring-1 ring-gray-200';
};

const closeModal = () => {
    showModal.value = false;
    editingOffice.value = null;

    form.defaults(initialForm());
    form.reset();
};
</script>

<template>
    <Head title="Oficinas" />

    <AppLayout>
        <div class="space-y-6 p-6">
            <!-- HEADER -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Oficinas</h1>
                    <p class="text-sm text-gray-500">
                        Configuración de remitentes de notificaciones.
                    </p>
                </div>

                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white shadow transition hover:bg-indigo-700"
                >
                    <Plus class="h-4 w-4" />
                    Nueva Oficina
                </button>
            </div>

            <!-- TABLA -->
            <div class="overflow-x-auto rounded-xl bg-white shadow">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3 text-left">Nombre</th>
                            <th class="p-3 text-left">Código</th>
                            <th class="p-3 text-left">Correo</th>
                            <th class="p-3 text-left">Nivel</th>
                            <th class="p-3 text-left">Estado</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="office in offices"
                            :key="office.id"
                            class="border-t transition hover:bg-gray-50"
                        >
                            <td class="p-3 font-medium">{{ office.name }}</td>
                            <td class="p-3 text-gray-500">{{ office.code }}</td>
                            <td class="p-3">
                                <div>
                                    <div>{{ office.email }}</div>
                                    <div
                                        v-if="office.cc_email"
                                        class="text-xs text-gray-400"
                                    >
                                        CC: {{ office.cc_email }}
                                    </div>
                                </div>
                            </td>
                            <td class="p-3">
                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="levelClasses(office.level)"
                                >
                                    Nivel {{ office.level }}
                                </span>
                            </td>
                            <td class="p-3">
                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="statusClasses(office.is_active)"
                                >
                                    {{
                                        office.is_active ? 'Activo' : 'Inactivo'
                                    }}
                                </span>
                            </td>
                            <td class="p-3">
                                <div class="flex justify-center gap-2">
                                    <button
                                        @click="openEdit(office)"
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-indigo-600 transition hover:bg-indigo-600 hover:text-white"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="remove(office)"
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-rose-600 transition hover:bg-rose-600 hover:text-white"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="offices.length === 0">
                            <td
                                colspan="6"
                                class="p-6 text-center text-gray-500"
                            >
                                No hay oficinas registradas
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- PAGINACIÓN -->
                <div
                    v-if="paginationMeta"
                    class="flex items-center justify-between border-t border-gray-100 bg-gray-50/60 px-6 py-4 backdrop-blur-sm"
                >
                    <div class="text-sm text-gray-600">
                        Mostrando
                        <span class="font-semibold text-gray-900">{{
                            paginationMeta?.from
                        }}</span>
                        a
                        <span class="font-semibold text-gray-900">{{
                            paginationMeta?.to
                        }}</span>
                        de
                        <span class="font-bold text-indigo-600">{{
                            paginationMeta?.total
                        }}</span>
                        oficinas
                    </div>
                    <nav
                        class="flex items-center gap-1 rounded-xl border border-gray-200 bg-white p-1 shadow-sm"
                    >
                        <button
                            v-for="(link, index) in paginationLinks"
                            :key="index"
                            v-html="link.label"
                            :disabled="!link.url"
                            @click="
                                link.url &&
                                router.visit(link.url, { preserveScroll: true })
                            "
                            class="flex h-9 min-w-[36px] items-center justify-center rounded-lg px-3 text-sm font-medium transition-all duration-200"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white shadow-md'
                                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                                !link.url && 'cursor-not-allowed opacity-40',
                            ]"
                        />
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
    </AppLayout>
</template>
