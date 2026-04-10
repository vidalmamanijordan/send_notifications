<script setup lang="ts">
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { Building2, Hash } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Campus {
    id: number;
    name: string;
    code?: string;
}

const props = defineProps<{
    show: boolean;
    campus: Campus | null;
}>();

const emit = defineEmits(['close']);

const isEdit = computed(() => !!props.campus);

const form = useForm({
    name: '',
    code: '',
});

watch(
    () => props.show,
    (value) => {
        if (value) {
            form.clearErrors();
            if (props.campus) {
                form.name = props.campus.name;
                form.code = props.campus.code ?? '';
            } else {
                form.reset();
            }
        }
    },
);

const submit = () => {
    if (isEdit.value && props.campus) {
        form.put(route('admin.campus.update', props.campus.id), {
            onSuccess: () => {
                emit('close');
                form.reset();
            },
        });
    } else {
        form.post(route('admin.campus.store'), {
            onSuccess: () => {
                emit('close');
                form.reset();
            },
        });
    }
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
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                    <Building2 class="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <DialogTitle class="text-base font-bold text-white">
                                        {{ isEdit ? 'Editar Campus' : 'Nuevo Campus' }}
                                    </DialogTitle>
                                    <p class="text-xs text-[#68c8fb]">
                                        {{ isEdit ? 'Modifica los datos del campus' : 'Completa los campos para crear un nuevo campus' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div class="space-y-5 p-6">
                            <!-- Nombre -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                    <Building2 class="h-3.5 w-3.5 text-[#68c8fb]" />
                                    Nombre <span class="ml-auto font-normal text-red-400 normal-case">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ej: Campus Central"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                    :class="{ 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                            </div>

                            <!-- Código -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                    <Hash class="h-3.5 w-3.5 text-[#68c8fb]" />
                                    Código
                                </label>
                                <input
                                    v-model="form.code"
                                    type="text"
                                    placeholder="Ej: CAM-01"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                    :class="{ 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100': form.errors.code }"
                                />
                                <p v-if="form.errors.code" class="text-xs text-red-500">{{ form.errors.code }}</p>
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
                                    {{ form.processing ? 'Guardando...' : isEdit ? 'Actualizar' : 'Guardar' }}
                                </button>
                            </div>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
