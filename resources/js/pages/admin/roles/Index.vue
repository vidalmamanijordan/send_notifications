<script setup lang="ts">
import RoleModal from '@/components/roles/RoleModal.vue';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Lock, Pencil, Plus, Shield, ShieldCheck, Trash2, Users } from 'lucide-vue-next';
import { ref } from 'vue';

interface Permission {
    id: number;
    name: string;
}

interface RoleItem {
    id: number;
    name: string;
    users_count: number;
    permissions: Permission[];
}

const props = defineProps<{
    roles: RoleItem[];
    permissions: Record<string, Permission[]>;
    systemRoles: string[];
}>();

const Swal = useSwal();

const showModal = ref(false);
const selectedRole = ref<RoleItem | null>(null);

const activeAction = ref<{ id: number | null; type: 'edit' | 'delete' | null }>({ id: null, type: null });

const setActive = (id: number, type: 'edit' | 'delete') => {
    activeAction.value = { id, type };
};

const resetActive = () => {
    setTimeout(() => { activeAction.value = { id: null, type: null }; }, 400);
};

const openCreateModal = () => {
    selectedRole.value = null;
    showModal.value = true;
};

const openEditModal = (role: RoleItem) => {
    setActive(role.id, 'edit');
    selectedRole.value = role;
    showModal.value = true;
};

const deleteRole = (role: RoleItem) => {
    setActive(role.id, 'delete');

    Swal.fire({
        title: '¿Eliminar rol?',
        html: `El rol <strong>${role.name}</strong> será eliminado permanentemente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#087ab1',
        cancelButtonColor: '#6b7280',
        focusCancel: true,
    }).then((result) => {
        if (!result.isConfirmed) { resetActive(); return; }

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
            if (!second.isConfirmed) { resetActive(); return; }

            router.delete(route('admin.roles.destroy', role.id), {
                preserveScroll: true,
                onFinish: () => resetActive(),
            });
        });
    });
};

const roleBadgeClass: Record<string, string> = {
    superadmin: 'bg-purple-100 text-purple-700 border border-purple-200',
    admin: 'bg-indigo-100 text-indigo-700 border border-indigo-200',
    administrativo: 'bg-sky-100 text-sky-700 border border-sky-200',
};

const totalPermissions = Object.values(props.permissions).flat().length;

const groupLabels: Record<string, string> = {
    users: 'Usuarios',
    campus: 'Campus',
    academicPeriods: 'Periodos',
    faculties: 'Facultades',
    programs: 'Programas',
    courses: 'Cursos',
    teachers: 'Docentes',
    excelUploads: 'Importaciones',
    expiredEvaluations: 'Evaluaciones',
    offices: 'Oficinas',
    notificationTemplates: 'Plantillas',
    notificationBatches: 'Lotes',
};
</script>

<template>
    <AppLayout>
        <Head title="Roles y Permisos" />

        <div class="space-y-6 px-6 py-6">
            <!-- HEADER -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#087ab1] shadow-md">
                        <Shield class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Roles y Permisos</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ roles.length }} rol{{ roles.length !== 1 ? 'es' : '' }} · {{ totalPermissions }} permisos disponibles
                        </p>
                    </div>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-4 py-2.5 text-sm font-medium text-white shadow-md transition hover:from-[#066a98] hover:to-[#4fbdf5] active:scale-95"
                >
                    <Plus class="h-4 w-4" /> Nuevo rol
                </button>
            </div>

            <!-- TABLA -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-linear-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Rol</th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase sm:table-cell dark:text-gray-400">Usuarios</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Permisos</th>
                            <th class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400">Módulos con acceso</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        <tr
                            v-for="role in roles"
                            :key="role.id"
                            class="group transition-all duration-150 hover:bg-[#68c8fb]/2 hover:shadow-[inset_3px_0_0_#087ab1] dark:hover:bg-[#68c8fb]/10 dark:hover:shadow-[inset_3px_0_0_#68c8fb]"
                        >
                            <!-- Rol -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#087ab1]/10">
                                        <ShieldCheck class="h-4 w-4 text-[#087ab1]" />
                                    </div>
                                    <div>
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            :class="roleBadgeClass[role.name] ?? 'bg-gray-100 text-gray-700 border border-gray-200'"
                                        >
                                            {{ role.name }}
                                        </span>
                                        <span v-if="systemRoles.includes(role.name)" class="ml-2 text-xs text-gray-400">sistema</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Usuarios -->
                            <td class="hidden px-5 py-4 sm:table-cell">
                                <div class="flex items-center gap-1.5">
                                    <Users class="h-3.5 w-3.5 text-gray-400" />
                                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ role.users_count }}</span>
                                </div>
                            </td>

                            <!-- Permisos count -->
                            <td class="px-5 py-4">
                                <div v-if="role.name === 'superadmin'" class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">
                                        <ShieldCheck class="h-3 w-3" /> Todos
                                    </span>
                                </div>
                                <div v-else class="flex items-center gap-2">
                                    <div class="h-1.5 w-24 overflow-hidden rounded-full bg-gray-100">
                                        <div
                                            class="h-full rounded-full bg-linear-to-r from-[#087ab1] to-[#68c8fb] transition-all"
                                            :style="{ width: `${Math.round((role.permissions.length / totalPermissions) * 100)}%` }"
                                        />
                                    </div>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ role.permissions.length }}<span class="text-gray-400">/{{ totalPermissions }}</span>
                                    </span>
                                </div>
                            </td>

                            <!-- Módulos -->
                            <td class="hidden px-5 py-4 lg:table-cell">
                                <div v-if="role.name === 'superadmin'" class="text-xs text-gray-400">Todos los módulos</div>
                                <div v-else class="flex flex-wrap gap-1">
                                    <template v-for="[group, perms] in Object.entries(permissions)" :key="group">
                                        <span
                                            v-if="perms.some((p) => role.permissions.map((rp) => rp.name).includes(p.name))"
                                            class="inline-block rounded-md bg-[#087ab1]/8 px-2 py-0.5 text-xs text-[#087ab1]"
                                        >
                                            {{ groupLabels[group] ?? group }}
                                        </span>
                                    </template>
                                    <span v-if="role.permissions.length === 0" class="text-xs text-gray-400">Sin permisos</span>
                                </div>
                            </td>

                            <!-- Acciones -->
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        @click="openEditModal(role)"
                                        title="Editar rol"
                                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="activeAction.id === role.id && activeAction.type === 'edit'
                                            ? 'bg-[#087ab1] text-white shadow-md'
                                            : 'text-[#087ab1] hover:bg-[#087ab1] hover:text-white'"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                    </button>
                                    <button
                                        v-if="!systemRoles.includes(role.name)"
                                        @click="deleteRole(role)"
                                        title="Eliminar rol"
                                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="activeAction.id === role.id && activeAction.type === 'delete'
                                            ? 'bg-red-600 text-white shadow-md'
                                            : 'text-red-500 hover:bg-red-600 hover:text-white'"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                    <div
                                        v-else
                                        title="Rol protegido del sistema"
                                        class="flex h-8 w-8 items-center justify-center rounded-full text-gray-300"
                                    >
                                        <Lock class="h-3.5 w-3.5" />
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="roles.length === 0">
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10">
                                        <Shield class="h-7 w-7 text-[#087ab1]/60" />
                                    </div>
                                    <p class="text-sm font-medium text-gray-500">No hay roles registrados</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- INFO PERMISOS -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <ShieldCheck class="h-4 w-4 text-[#087ab1]" />
                    Todos los permisos del sistema
                </h2>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="[group, perms] in Object.entries(permissions)"
                        :key="group"
                        class="rounded-xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <p class="mb-2 text-xs font-semibold text-gray-500 uppercase dark:text-gray-400">
                            {{ groupLabels[group] ?? group }}
                        </p>
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="perm in perms"
                                :key="perm.id"
                                class="inline-block rounded-md bg-[#087ab1]/8 px-2 py-0.5 text-xs text-[#087ab1]"
                            >
                                {{ perm.name.split('.')[1] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <RoleModal
            :show="showModal"
            :role="selectedRole"
            :permissions="permissions"
            :system-roles="systemRoles"
            @close="showModal = false; selectedRole = null"
        />
    </AppLayout>
</template>
