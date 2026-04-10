<script setup lang="ts">
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { CheckSquare2, ChevronDown, ChevronRight, Shield, ShieldCheck, Tag } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Permission {
    id: number;
    name: string;
}

interface RoleItem {
    id: number;
    name: string;
    permissions: Permission[];
}

const props = defineProps<{
    show: boolean;
    role: RoleItem | null;
    permissions: Record<string, Permission[]>;
    systemRoles: string[];
}>();

const emit = defineEmits(['close']);

const groupLabels: Record<string, string> = {
    users: 'Usuarios',
    campus: 'Campus',
    academicPeriods: 'Periodos Académicos',
    faculties: 'Facultades',
    programs: 'Programas',
    courses: 'Cursos',
    teachers: 'Docentes',
    excelUploads: 'Importaciones Excel',
    expiredEvaluations: 'Evaluaciones Vencidas',
    offices: 'Oficinas',
    notificationTemplates: 'Plantillas de Notificación',
    notificationBatches: 'Lotes de Notificación',
};

const permissionLabels: Record<string, string> = {
    viewAny: 'Ver listado',
    create: 'Crear',
    update: 'Editar',
    delete: 'Eliminar',
    show: 'Ver detalle',
    assignRole: 'Asignar rol',
    switch: 'Cambiar periodo activo',
    attachTemplate: 'Adjuntar plantilla',
    assignOffice: 'Asignar oficina',
    send: 'Enviar notificaciones',
    resend: 'Reenviar individual',
};

const isEdit = computed(() => !!props.role);
const isSystemRole = computed(() => props.role ? props.systemRoles.includes(props.role.name) : false);
const isSuperadmin = computed(() => props.role?.name === 'superadmin');

const form = useForm({
    name: '',
    permissions: [] as string[],
});

const collapsedGroups = ref<Record<string, boolean>>({});

const toggleGroup = (group: string) => {
    collapsedGroups.value[group] = !collapsedGroups.value[group];
};

const allGroupSelected = (perms: Permission[]) =>
    perms.every((p) => form.permissions.includes(p.name));

const someGroupSelected = (perms: Permission[]) =>
    perms.some((p) => form.permissions.includes(p.name));

const toggleGroup2 = (perms: Permission[]) => {
    if (allGroupSelected(perms)) {
        form.permissions = form.permissions.filter((n) => !perms.map((p) => p.name).includes(n));
    } else {
        const toAdd = perms.map((p) => p.name).filter((n) => !form.permissions.includes(n));
        form.permissions = [...form.permissions, ...toAdd];
    }
};

watch(
    () => props.show,
    (value) => {
        if (value) {
            collapsedGroups.value = {};
            form.clearErrors();
            if (props.role) {
                form.name = props.role.name;
                form.permissions = props.role.permissions.map((p) => p.name);
            } else {
                form.reset();
            }
        }
    },
);

const submit = () => {
    if (isEdit.value && props.role) {
        form.put(route('admin.roles.update', props.role.id), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('admin.roles.store'), {
            onSuccess: () => { emit('close'); form.reset(); },
        });
    }
};

const permissionGroups = computed(() => Object.entries(props.permissions));
</script>

<template>
    <TransitionRoot appear :show="show" as="template">
        <Dialog @close="emit('close')" class="relative z-50">
            <TransitionChild
                as="template"
                enter="duration-500 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-300 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/40 backdrop-blur-[1px]" />
            </TransitionChild>

            <div class="fixed inset-0 flex items-center justify-center p-6">
                <TransitionChild
                    enter="duration-500 ease-out"
                    enter-from="opacity-0 scale-95 translate-y-6"
                    enter-to="opacity-100 scale-100 translate-y-0"
                    leave="duration-300 ease-in"
                    leave-from="opacity-100 scale-100 translate-y-0"
                    leave-to="opacity-0 scale-95 translate-y-4"
                    class="relative w-full max-w-2xl"
                >
                    <DialogPanel class="flex max-h-[90vh] w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
                        <!-- HEADER -->
                        <div class="relative shrink-0 overflow-hidden bg-linear-to-br from-[#087ab1] to-[#68c8fb] px-6 py-6">
                            <div class="pointer-events-none absolute -top-12 -right-12 h-52 w-52 rounded-full bg-white/8" />
                            <div class="pointer-events-none absolute right-40 -bottom-8 h-36 w-36 rounded-full bg-white/5" />
                            <div class="pointer-events-none absolute bottom-0 left-1/3 h-24 w-24 rounded-full bg-white/5" />
                            <div class="pointer-events-none absolute -top-2 right-28 h-[160%] w-px rotate-22 rounded-full bg-white/20" />
                            <div class="pointer-events-none absolute -top-2 right-20 h-[160%] w-px rotate-22 rounded-full bg-white/14" />
                            <div class="pointer-events-none absolute -top-2 right-12 h-[160%] w-px rotate-22 rounded-full bg-white/8" />
                            <div class="relative flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                    <Shield class="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <DialogTitle class="text-base font-bold text-white">
                                        {{ isEdit ? `Editar Rol: ${role?.name}` : 'Nuevo Rol' }}
                                    </DialogTitle>
                                    <p class="text-xs text-[#68c8fb]">
                                        {{ isEdit ? 'Configura los permisos asignados a este rol' : 'Define el nombre y los permisos del nuevo rol' }}
                                    </p>
                                </div>
                                <div v-if="isSuperadmin" class="ml-auto">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/30">
                                        <ShieldCheck class="h-3 w-3" /> Acceso total
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div class="flex-1 overflow-y-auto">
                            <div class="space-y-5 p-6">
                                <!-- Nombre del rol -->
                                <div class="space-y-1.5">
                                    <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                        <Tag class="h-3.5 w-3.5 text-[#68c8fb]" />
                                        Nombre del rol
                                        <span v-if="!isSystemRole" class="ml-auto font-normal text-red-400 normal-case">*</span>
                                    </label>
                                    <div class="relative">
                                        <Tag class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            :disabled="isSystemRole"
                                            placeholder="Ej: coordinador"
                                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pr-3.5 pl-9 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                                            :class="{ 'border-red-300 bg-red-50': form.errors.name }"
                                        />
                                    </div>
                                    <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                                    <p v-if="isSystemRole" class="text-xs text-amber-600">Los roles del sistema no pueden renombrarse.</p>
                                </div>

                                <!-- Permisos -->
                                <div class="space-y-2">
                                    <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                        <CheckSquare2 class="h-3.5 w-3.5 text-[#68c8fb]" />
                                        Permisos asignados
                                        <span v-if="!isSuperadmin" class="ml-auto font-normal normal-case text-gray-400">
                                            {{ form.permissions.length }} seleccionado{{ form.permissions.length !== 1 ? 's' : '' }}
                                        </span>
                                    </label>

                                    <!-- Superadmin: acceso total badge -->
                                    <div v-if="isSuperadmin" class="rounded-xl border border-[#087ab1]/20 bg-[#087ab1]/5 px-4 py-3 text-sm text-[#087ab1]">
                                        <div class="flex items-center gap-2">
                                            <ShieldCheck class="h-4 w-4 shrink-0" />
                                            <span>El rol <strong>superadmin</strong> tiene acceso a todos los permisos del sistema de forma automática.</span>
                                        </div>
                                    </div>

                                    <!-- Grupos de permisos -->
                                    <div v-else class="space-y-2">
                                        <div
                                            v-for="[group, perms] in permissionGroups"
                                            :key="group"
                                            class="overflow-hidden rounded-xl border border-gray-200 bg-white"
                                        >
                                            <!-- Cabecera del grupo -->
                                            <button
                                                type="button"
                                                @click="toggleGroup(group)"
                                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left transition hover:bg-gray-50"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :checked="allGroupSelected(perms)"
                                                    :indeterminate="!allGroupSelected(perms) && someGroupSelected(perms)"
                                                    @click.stop="toggleGroup2(perms)"
                                                    class="h-4 w-4 rounded border-gray-300 text-[#087ab1] accent-[#087ab1]"
                                                />
                                                <span class="flex-1 text-sm font-semibold text-gray-700">
                                                    {{ groupLabels[group] ?? group }}
                                                </span>
                                                <span class="text-xs text-gray-400">
                                                    {{ perms.filter((p) => form.permissions.includes(p.name)).length }}/{{ perms.length }}
                                                </span>
                                                <ChevronDown v-if="!collapsedGroups[group]" class="h-4 w-4 text-gray-400" />
                                                <ChevronRight v-else class="h-4 w-4 text-gray-400" />
                                            </button>

                                            <!-- Permisos del grupo -->
                                            <div v-if="!collapsedGroups[group]" class="grid grid-cols-2 gap-1 border-t border-gray-100 bg-gray-50/60 p-3">
                                                <label
                                                    v-for="perm in perms"
                                                    :key="perm.id"
                                                    class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-1.5 text-sm text-gray-600 transition hover:bg-white hover:shadow-sm"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :value="perm.name"
                                                        v-model="form.permissions"
                                                        class="h-4 w-4 rounded border-gray-300 text-[#087ab1] accent-[#087ab1]"
                                                    />
                                                    <span>{{ permissionLabels[perm.name.split('.')[1]] ?? perm.name.split('.')[1] }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <p v-if="form.errors.permissions" class="text-xs text-red-500">{{ form.errors.permissions }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="flex shrink-0 items-center justify-between border-t border-gray-100 bg-gray-50/70 px-6 py-4">
                            <p class="text-xs text-gray-400">
                                <span v-if="!isSystemRole"><span class="text-red-400">*</span> Nombre requerido</span>
                                <span v-else-if="isSuperadmin" class="text-amber-600">Los permisos de superadmin no se pueden modificar</span>
                                <span v-else class="text-amber-600">El nombre del rol no puede cambiarse</span>
                            </p>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    @click="emit('close')"
                                    class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                                >
                                    Cerrar
                                </button>
                                <button
                                    v-if="!isSuperadmin"
                                    type="button"
                                    @click="submit"
                                    :disabled="form.processing"
                                    class="flex items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#087ab1]/30 transition-all hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    {{ form.processing ? 'Guardando...' : isEdit ? 'Actualizar rol' : 'Crear rol' }}
                                </button>
                            </div>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
