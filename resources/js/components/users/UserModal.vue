<script setup lang="ts">
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Lock, Mail, ShieldCheck, User } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Role {
    id: number;
    name: string;
}

interface UserItem {
    id: number;
    name: string;
    email: string;
    roles?: { id: number; name: string }[];
}

const props = defineProps<{
    show: boolean;
    user: UserItem | null;
    roles: Role[];
}>();

const emit = defineEmits(['close']);

const showPassword = ref(false);
const showConfirm = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
});

const isEdit = computed(() => !!props.user);

watch(
    () => props.show,
    (value) => {
        if (value) {
            showPassword.value = false;
            showConfirm.value = false;
            form.clearErrors();
            if (props.user) {
                form.name = props.user.name;
                form.email = props.user.email;
                form.password = '';
                form.password_confirmation = '';
                form.role = props.user.roles?.[0]?.name ?? '';
            } else {
                form.reset();
            }
        }
    },
);

const submit = () => {
    if (isEdit.value && props.user) {
        form.put(route('admin.users.update', props.user.id), {
            onSuccess: () => {
                emit('close');
                form.reset();
            },
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => {
                emit('close');
                form.reset();
            },
        });
    }
};

const initials = computed(() => {
    if (!props.user?.name) return '?';
    return props.user.name
        .split(' ')
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

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
                    class="relative w-full max-w-lg"
                >
                    <DialogPanel class="w-full overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
                        <!-- HEADER -->
                        <div class="relative overflow-hidden bg-linear-to-br from-[#087ab1] to-[#68c8fb] px-6 py-6">
                            <div class="pointer-events-none absolute -top-12 -right-12 h-52 w-52 rounded-full bg-white/8" />
                            <div class="pointer-events-none absolute right-40 -bottom-8 h-36 w-36 rounded-full bg-white/5" />
                            <div class="pointer-events-none absolute bottom-0 left-1/3 h-24 w-24 rounded-full bg-white/5" />
                            <div class="pointer-events-none absolute -top-2 right-28 h-[160%] w-px rotate-22 rounded-full bg-white/20" />
                            <div class="pointer-events-none absolute -top-2 right-20 h-[160%] w-px rotate-22 rounded-full bg-white/14" />
                            <div class="pointer-events-none absolute -top-2 right-12 h-[160%] w-px rotate-22 rounded-full bg-white/8" />
                            <div class="relative flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 text-sm font-bold text-white ring-1 ring-white/25">
                                    <span v-if="isEdit">{{ initials }}</span>
                                    <User v-else class="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <DialogTitle class="text-base font-bold text-white">
                                        {{ isEdit ? 'Editar Usuario' : 'Nuevo Usuario' }}
                                    </DialogTitle>
                                    <p class="text-xs text-[#68c8fb]">
                                        {{ isEdit ? 'Modifica los datos del usuario' : 'Completa los campos para crear el usuario' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div class="space-y-5 p-6">
                            <!-- Nombre -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                    <User class="h-3.5 w-3.5 text-[#68c8fb]" />
                                    Nombre completo <span class="ml-auto font-normal text-red-400 normal-case">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ej: Juan Pérez"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                    :class="{ 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                            </div>

                            <!-- Email -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                    <Mail class="h-3.5 w-3.5 text-[#68c8fb]" />
                                    Correo electrónico <span class="ml-auto font-normal text-red-400 normal-case">*</span>
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="correo@ejemplo.com"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                    :class="{ 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
                            </div>

                            <!-- Rol -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                    <ShieldCheck class="h-3.5 w-3.5 text-[#68c8fb]" />
                                    Rol del sistema <span class="ml-auto font-normal text-red-400 normal-case">*</span>
                                </label>
                                <div class="relative">
                                    <select
                                        v-model="form.role"
                                        class="w-full appearance-none rounded-xl border border-gray-200 bg-gray-50 py-2.5 pr-8 pl-3.5 text-sm text-gray-900 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                        :class="{ 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100': form.errors.role }"
                                    >
                                        <option value="" disabled>Seleccionar rol...</option>
                                        <option v-for="r in roles" :key="r.id" :value="r.name">
                                            {{ roleLabels[r.name] ?? r.name }}
                                        </option>
                                    </select>
                                    <div class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-gray-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                <div v-if="form.role" class="mt-1">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                        :class="roleBadgeClass[form.role] ?? 'bg-gray-100 text-gray-700'"
                                    >
                                        <ShieldCheck class="h-3 w-3" />
                                        {{ roleLabels[form.role] ?? form.role }}
                                    </span>
                                </div>
                                <p v-if="form.errors.role" class="text-xs text-red-500">{{ form.errors.role }}</p>
                            </div>

                            <!-- Divisor contraseña -->
                            <div class="flex items-center gap-2 pt-1">
                                <div class="h-px flex-1 bg-gray-200" />
                                <span class="text-xs text-gray-400">
                                    {{ isEdit ? 'Nueva contraseña (opcional)' : 'Contraseña' }}
                                </span>
                                <div class="h-px flex-1 bg-gray-200" />
                            </div>

                            <!-- Contraseña -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                    <Lock class="h-3.5 w-3.5 text-[#68c8fb]" />
                                    Contraseña
                                    <span v-if="!isEdit" class="ml-auto font-normal text-red-400 normal-case">*</span>
                                    <span v-else class="ml-auto font-normal text-gray-400 normal-case">(dejar vacío para no cambiar)</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Mínimo 8 caracteres"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pr-10 pl-3.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                        :class="{ 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100': form.errors.password }"
                                    />
                                    <button type="button" @click="showPassword = !showPassword" class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <EyeOff v-if="showPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</p>
                            </div>

                            <!-- Confirmar contraseña -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                    <Lock class="h-3.5 w-3.5 text-[#68c8fb]" />
                                    Confirmar contraseña
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.password_confirmation"
                                        :type="showConfirm ? 'text' : 'password'"
                                        placeholder="Repite la contraseña"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pr-10 pl-3.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                    />
                                    <button type="button" @click="showConfirm = !showConfirm" class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <EyeOff v-if="showConfirm" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50/70 px-6 py-4">
                            <p class="text-xs text-gray-400"><span class="text-red-400">*</span> Los campos marcados son requeridos</p>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    @click="emit('close')"
                                    class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                                >
                                    Cerrar
                                </button>
                                <button
                                    type="button"
                                    @click="submit"
                                    :disabled="form.processing"
                                    class="flex items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#087ab1]/30 transition-all hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    {{ form.processing ? 'Guardando...' : isEdit ? 'Actualizar' : 'Crear usuario' }}
                                </button>
                            </div>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
