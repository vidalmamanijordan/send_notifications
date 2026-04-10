<script setup lang="ts">
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { CalendarDays, CheckCircle, Hash } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface AcademicPeriod {
    id: number;
    code: string;
    name: string;
    start_date: string;
    end_date: string;
    status: 'active' | 'closed';
}

const props = defineProps<{
    show: boolean;
    mode: 'create' | 'edit';
    academicPeriod?: AcademicPeriod | null;
}>();

const emit = defineEmits(['close']);

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    code: '',
    name: '',
    start_date: '',
    end_date: '',
    status: 'active' as 'active' | 'closed',
});

watch(
    () => props.academicPeriod,
    (value) => {
        form.clearErrors();
        if (value && props.mode === 'edit') {
            form.code = value.code;
            form.name = value.name;
            form.start_date = value.start_date;
            form.end_date = value.end_date;
            form.status = value.status;
        } else if (props.mode === 'create') {
            form.reset();
            form.status = 'active';
        }
    },
    { immediate: true },
);

watch(
    () => props.show,
    (visible) => {
        if (!visible) {
            form.clearErrors();
        }
    },
);

const submit = () => {
    if (isEdit.value && props.academicPeriod) {
        form.put(route('admin.academic-periods.update', props.academicPeriod.id), {
            onSuccess: () => {
                emit('close');
                form.reset();
            },
        });
    } else {
        form.post(route('admin.academic-periods.store'), {
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
                                    <CalendarDays class="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <DialogTitle class="text-base font-bold text-white">
                                        {{ isEdit ? 'Editar Periodo Académico' : 'Nuevo Periodo Académico' }}
                                    </DialogTitle>
                                    <p class="text-xs text-[#68c8fb]">
                                        {{ isEdit ? 'Modifica los datos del periodo' : 'Completa los campos para crear un nuevo periodo' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div class="space-y-5 p-6">
                            <!-- Código -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                    <Hash class="h-3.5 w-3.5 text-[#68c8fb]" />
                                    Código <span class="ml-auto font-normal text-red-400 normal-case">*</span>
                                </label>
                                <input
                                    v-model="form.code"
                                    type="text"
                                    placeholder="Ej: 2025-I"
                                    :readonly="isEdit"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                    :class="[
                                        { 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100': form.errors.code },
                                        isEdit ? 'cursor-not-allowed bg-gray-100 focus:border-gray-200 focus:ring-0' : '',
                                    ]"
                                />
                                <p v-if="form.errors.code" class="text-xs text-red-500">{{ form.errors.code }}</p>
                            </div>

                            <!-- Nombre -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                    <CalendarDays class="h-3.5 w-3.5 text-[#68c8fb]" />
                                    Nombre <span class="ml-auto font-normal text-red-400 normal-case">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ej: Semestre 2025 - I"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                    :class="{ 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                            </div>

                            <!-- Fechas -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                        <CalendarDays class="h-3.5 w-3.5 text-[#68c8fb]" />
                                        Fecha inicio <span class="ml-auto font-normal text-red-400 normal-case">*</span>
                                    </label>
                                    <input
                                        v-model="form.start_date"
                                        type="date"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                        :class="{ 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100': form.errors.start_date }"
                                    />
                                    <p v-if="form.errors.start_date" class="text-xs text-red-500">{{ form.errors.start_date }}</p>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                        <CalendarDays class="h-3.5 w-3.5 text-[#68c8fb]" />
                                        Fecha fin <span class="ml-auto font-normal text-red-400 normal-case">*</span>
                                    </label>
                                    <input
                                        v-model="form.end_date"
                                        type="date"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                        :class="{ 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100': form.errors.end_date }"
                                    />
                                    <p v-if="form.errors.end_date" class="text-xs text-red-500">{{ form.errors.end_date }}</p>
                                </div>
                            </div>

                            <!-- Toggle estado -->
                            <div class="flex items-center justify-between rounded-xl border border-[#68c8fb]/30 bg-[#68c8fb]/10 px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <CheckCircle class="h-4 w-4 text-[#68c8fb]" />
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ form.status === 'active' ? 'Periodo activo' : 'Periodo cerrado' }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    @click="form.status = form.status === 'active' ? 'closed' : 'active'"
                                    :class="[
                                        'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:ring-2 focus:ring-[#087ab1] focus:ring-offset-1 focus:outline-none',
                                        form.status === 'active' ? 'bg-[#087ab1]' : 'bg-gray-200',
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-md transition-transform',
                                            form.status === 'active' ? 'translate-x-[18px]' : 'translate-x-[3px]',
                                        ]"
                                    />
                                </button>
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
