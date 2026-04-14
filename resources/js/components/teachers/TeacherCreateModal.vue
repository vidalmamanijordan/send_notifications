<script setup lang="ts">
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { UserRound, X } from 'lucide-vue-next';
import { watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{ show: boolean }>();
const emit = defineEmits(['close', 'success']);

const form = useForm({
    dni: '',
    full_name: '',
    email: '',
});

watch(
    () => props.show,
    (val) => {
        if (!val) {
            form.reset();
            form.clearErrors();
        }
    },
);

const submit = () => {
    form.post(route('admin.teachers.store'), {
        onSuccess: () => emit('success'),
    });
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
                    class="relative w-full max-w-md"
                >
                    <DialogPanel class="w-full overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-gray-900 dark:ring-white/10">

                        <!-- HEADER -->
                        <div class="relative overflow-hidden bg-linear-to-br from-[#087ab1] to-[#68c8fb] px-6 py-5">
                            <div class="pointer-events-none absolute -top-12 -right-12 h-52 w-52 rounded-full bg-white/8" />
                            <div class="pointer-events-none absolute -top-2 right-12 h-[160%] w-px rotate-22 rounded-full bg-white/8" />

                            <div class="relative flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                    <UserRound class="h-5 w-5 text-white" />
                                </div>
                                <div class="flex-1">
                                    <DialogTitle class="text-base font-bold text-white">
                                        Nuevo Docente
                                    </DialogTitle>
                                    <p class="text-xs text-[#ceeeff]">
                                        Completa los datos para registrar un docente
                                    </p>
                                </div>
                                <button
                                    @click="emit('close')"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-white transition hover:bg-white/20"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div class="space-y-4 p-6">

                            <!-- DNI -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                    DNI <span class="font-normal text-red-400 normal-case">Requerido</span>
                                </label>
                                <input
                                    v-model="form.dni"
                                    type="text"
                                    placeholder="Ej: 12345678"
                                    maxlength="20"
                                    @keydown.enter="submit"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 transition focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                    :class="{ 'border-red-300 bg-red-50': form.errors.dni }"
                                />
                                <p v-if="form.errors.dni" class="text-xs text-red-500">{{ form.errors.dni }}</p>
                            </div>

                            <!-- Nombre completo -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                    Nombre Completo <span class="font-normal text-red-400 normal-case">Requerido</span>
                                </label>
                                <input
                                    v-model="form.full_name"
                                    type="text"
                                    placeholder="Apellidos y nombres"
                                    maxlength="255"
                                    @keydown.enter="submit"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 transition focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                    :class="{ 'border-red-300 bg-red-50': form.errors.full_name }"
                                />
                                <p v-if="form.errors.full_name" class="text-xs text-red-500">{{ form.errors.full_name }}</p>
                            </div>

                            <!-- Email -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                    Email <span class="font-normal text-gray-400 normal-case">Opcional</span>
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="correo@institución.edu"
                                    maxlength="255"
                                    @keydown.enter="submit"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 transition focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                    :class="{ 'border-red-300 bg-red-50': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
                            </div>

                        </div>

                        <!-- FOOTER -->
                        <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/70 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/50">
                            <button
                                type="button"
                                class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                @click="emit('close')"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#087ab1]/30 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="form.processing"
                                @click="submit"
                            >
                                <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <UserRound v-else class="h-4 w-4" />
                                {{ form.processing ? 'Guardando...' : 'Crear Docente' }}
                            </button>
                        </div>

                    </DialogPanel>
                </TransitionChild>
            </div>

        </Dialog>
    </TransitionRoot>
</template>
