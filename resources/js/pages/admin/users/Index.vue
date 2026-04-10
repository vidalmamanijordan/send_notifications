<script setup lang="ts">
import UserModal from '@/components/users/UserModal.vue';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircle,
    Pencil,
    Search,
    ShieldCheck,
    Trash2,
    UserPlus,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

const Swal = useSwal();

const translateLabel = (label: string): string =>
    label.replace('Previous', 'Anterior').replace('Next', 'Siguiente');

const isPageNumber = (label: string): boolean =>
    /^\d+$/.test(label.replace(/&[^;]+;/g, '').trim());

interface Role {
    id: number;
    name: string;
}

interface UserItem {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    roles: Role[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    users: {
        data: UserItem[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        search: string | null;
    };
    roles: Role[];
}>();

const showModal = ref(false);
const selectedUser = ref<UserItem | null>(null);

const search = ref(props.filters.search ?? '');
let searchTimeout: ReturnType<typeof setTimeout>;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('admin.users.index'),
            { search: value || undefined },
            { preserveState: true, replace: true },
        );
    }, 350);
});

const activeAction = ref<{ id: number | null; type: 'edit' | 'delete' | null }>({ id: null, type: null });

const setActive = (id: number, type: 'edit' | 'delete') => {
    activeAction.value = { id, type };
};

const resetActive = () => {
    setTimeout(() => { activeAction.value = { id: null, type: null }; }, 400);
};

const openCreateModal = () => {
    selectedUser.value = null;
    showModal.value = true;
};

const openEditModal = (user: UserItem) => {
    setActive(user.id, 'edit');
    selectedUser.value = user;
    showModal.value = true;
};

const deleteUser = (user: UserItem) => {
    setActive(user.id, 'delete');

    Swal.fire({
        title: '¿Eliminar usuario?',
        html: `El usuario <strong>${user.name}</strong> será eliminado permanentemente.`,
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

            router.delete(route('admin.users.destroy', user.id), {
                preserveScroll: true,
                onFinish: () => resetActive(),
            });
        });
    });
};

const formatDate = (date: string): string =>
    new Date(date).toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });

const getInitials = (name: string): string =>
    name.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase();

const roleBadgeClass: Record<string, string> = {
    superadmin: 'bg-purple-100 text-purple-700 border border-purple-200',
    admin: 'bg-indigo-100 text-indigo-700 border border-indigo-200',
    administrativo: 'bg-sky-100 text-sky-700 border border-sky-200',
};

const roleLabels: Record<string, string> = {
    superadmin: 'Super Admin',
    admin: 'Admin',
    administrativo: 'Administrativo',
};

const goToPage = (url: string | null) => {
    if (!url) return;
    router.visit(url, { preserveScroll: true, preserveState: true });
};
</script>

<template>
    <AppLayout>
        <Head title="Usuarios" />

        <div class="space-y-6 px-6 py-6">
            <!-- HEADER -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#087ab1] shadow-md">
                        <Users class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Usuarios del sistema</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ users.total }} usuario{{ users.total !== 1 ? 's' : '' }} registrado{{ users.total !== 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-4 py-2.5 text-sm font-medium text-white shadow-md transition hover:from-[#066a98] hover:to-[#4fbdf5] active:scale-95"
                >
                    <UserPlus class="h-4 w-4" /> Nuevo usuario
                </button>
            </div>

            <!-- BÚSQUEDA -->
            <div class="flex items-center gap-3">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nombre o correo..."
                        class="w-full rounded-xl border border-gray-300 bg-white py-2 pr-4 pl-9 text-sm shadow-sm transition focus:border-[#087ab1] focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"
                    />
                    <button v-if="search" @click="search = ''" class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        ×
                    </button>
                </div>
                <span v-if="users.from" class="text-sm text-gray-500 dark:text-gray-400">
                    {{ users.from }}–{{ users.to }} de {{ users.total }}
                </span>
            </div>

            <!-- TABLA -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-linear-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Usuario</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Correo</th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase md:table-cell dark:text-gray-400">Rol</th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase sm:table-cell dark:text-gray-400">Estado</th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400">Registrado</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="group transition-all duration-150 hover:bg-[#68c8fb]/2 hover:shadow-[inset_3px_0_0_#087ab1] dark:hover:bg-[#68c8fb]/10 dark:hover:shadow-[inset_3px_0_0_#68c8fb]"
                        >
                            <!-- Avatar + Nombre -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#087ab1]/10 text-xs font-bold text-[#087ab1]">
                                        {{ getInitials(user.name) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ user.name }}</p>
                                        <p class="text-xs text-gray-400">#{{ user.id }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Email -->
                            <td class="px-5 py-3.5">
                                <span class="text-sm text-gray-600 dark:text-gray-300">{{ user.email }}</span>
                            </td>

                            <!-- Rol -->
                            <td class="hidden px-5 py-3.5 md:table-cell">
                                <template v-if="user.roles?.length">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="roleBadgeClass[user.roles[0].name] ?? 'bg-gray-100 text-gray-600'"
                                    >
                                        <ShieldCheck class="h-3 w-3" />
                                        {{ roleLabels[user.roles[0].name] ?? user.roles[0].name }}
                                    </span>
                                </template>
                                <span v-else class="text-xs text-gray-400">Sin rol</span>
                            </td>

                            <!-- Estado verificación -->
                            <td class="hidden px-5 py-3.5 sm:table-cell">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="user.email_verified_at
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'"
                                >
                                    <CheckCircle v-if="user.email_verified_at" class="h-3 w-3" />
                                    <XCircle v-else class="h-3 w-3" />
                                    {{ user.email_verified_at ? 'Verificado' : 'Sin verificar' }}
                                </span>
                            </td>

                            <!-- Fecha -->
                            <td class="hidden px-5 py-3.5 lg:table-cell">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(user.created_at) }}</span>
                            </td>

                            <!-- Acciones -->
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        @click="openEditModal(user)"
                                        title="Editar usuario"
                                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="activeAction.id === user.id && activeAction.type === 'edit'
                                            ? 'bg-[#087ab1] text-white shadow-md'
                                            : 'text-[#087ab1] hover:bg-[#087ab1] hover:text-white'"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                    </button>
                                    <button
                                        @click="deleteUser(user)"
                                        title="Eliminar usuario"
                                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="activeAction.id === user.id && activeAction.type === 'delete'
                                            ? 'bg-red-600 text-white shadow-md'
                                            : 'text-red-500 hover:bg-red-600 hover:text-white'"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="users.data.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10 dark:bg-[#087ab1]/20">
                                        <Users class="h-7 w-7 text-[#087ab1]/60" />
                                    </div>
                                    <p class="text-sm font-medium text-gray-500">
                                        {{ search ? 'No se encontraron usuarios con ese criterio' : 'No hay usuarios registrados' }}
                                    </p>
                                    <button v-if="search" @click="search = ''" class="text-xs text-[#087ab1] hover:underline">
                                        Limpiar búsqueda
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div v-if="users.links && users.links.length > 3" class="flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <template v-if="users.from">
                        Mostrando
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ users.from }}</span>
                        a
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ users.to }}</span>
                        de
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ users.total }}</span>
                        resultados
                    </template>
                    <template v-else>Sin resultados</template>
                </p>
                <nav class="inline-flex gap-1">
                    <template v-for="link in users.links" :key="link.label">
                        <a
                            v-if="link.url"
                            :href="link.url"
                            @click.prevent="goToPage(link.url)"
                            class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-all"
                            :class="link.active && isPageNumber(link.label)
                                ? 'border-[#68c8fb] bg-[#68c8fb] text-white shadow-sm'
                                : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'"
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

        <!-- MODAL -->
        <UserModal
            :show="showModal"
            :user="selectedUser"
            :roles="roles"
            @close="showModal = false; resetActive();"
        />
    </AppLayout>
</template>
