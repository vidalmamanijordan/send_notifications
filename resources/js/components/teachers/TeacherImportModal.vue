<script setup lang="ts">
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { FileSpreadsheet, Upload, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{ show: boolean }>();
const emit = defineEmits(['close', 'success']);

const form = useForm({ file: null as File | null });
const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const formatFileSize = (bytes: number): string => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const handleFile = (file: File | null) => {
    if (!file) return;
    form.file = file;
};

const onFileChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    handleFile(input.files?.[0] ?? null);
};

const onDrop = (e: DragEvent) => {
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0] ?? null;
    if (file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls'))) {
        handleFile(file);
    }
};

const removeFile = () => {
    form.file = null;
    if (fileInput.value) fileInput.value.value = '';
};

watch(
    () => props.show,
    (val) => {
        if (!val) {
            form.reset();
            form.clearErrors();
            isDragging.value = false;
        }
    },
);

const submit = () => {
    form.post(route('admin.teachers.import'), {
        forceFormData: true,
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
                    class="relative w-full max-w-lg"
                >
                    <DialogPanel class="w-full overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-gray-900 dark:ring-white/10">

                        <!-- HEADER -->
                        <div class="relative overflow-hidden bg-linear-to-br from-[#087ab1] to-[#68c8fb] px-6 py-5">
                            <div class="pointer-events-none absolute -top-12 -right-12 h-52 w-52 rounded-full bg-white/8" />
                            <div class="pointer-events-none absolute right-40 -bottom-8 h-36 w-36 rounded-full bg-white/5" />
                            <div class="pointer-events-none absolute -top-2 right-20 h-[160%] w-px rotate-22 rounded-full bg-white/14" />
                            <div class="pointer-events-none absolute -top-2 right-12 h-[160%] w-px rotate-22 rounded-full bg-white/8" />

                            <div class="relative flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                    <FileSpreadsheet class="h-5 w-5 text-white" />
                                </div>
                                <div class="flex-1">
                                    <DialogTitle class="text-base font-bold text-white">
                                        Importar Docentes
                                    </DialogTitle>
                                    <p class="text-xs text-[#ceeeff]">
                                        Sube un archivo .xlsx con DNI, Nombre y Email
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
                        <div class="space-y-5 p-6">

                            <!-- Info columnas -->
                            <div class="rounded-xl border border-[#087ab1]/20 bg-[#087ab1]/5 px-4 py-3">
                                <p class="mb-2 text-xs font-semibold tracking-wide text-[#087ab1] uppercase dark:text-[#68c8fb]">
                                    Columnas esperadas
                                </p>
                                <div class="flex gap-3">
                                    <span v-for="col in ['A · DNI', 'B · Nombre Completo', 'C · Email']" :key="col"
                                        class="rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700"
                                    >
                                        {{ col }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs text-gray-400">
                                    La primera fila se omite (cabecera). Solo se crean docentes nuevos. Si el DNI ya existe pero sin correo, se actualiza únicamente el email.
                                </p>
                            </div>

                            <!-- Zona archivo -->
                            <div class="space-y-1.5">
                                <div
                                    v-if="form.file"
                                    class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-800 dark:bg-emerald-900/20"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                                            <FileSpreadsheet class="h-5 w-5 text-emerald-600" />
                                        </div>
                                        <div>
                                            <p class="max-w-[240px] truncate text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                {{ form.file.name }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatFileSize(form.file.size) }}
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeFile"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-100 hover:text-red-500 dark:hover:bg-red-900/30"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>

                                <div
                                    v-else
                                    class="relative flex cursor-pointer flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed p-10 transition-colors"
                                    :class="isDragging
                                        ? 'border-[#087ab1] bg-[#087ab1]/5'
                                        : 'border-gray-200 bg-gray-50 hover:border-[#087ab1]/50 hover:bg-[#087ab1]/5 dark:border-gray-700 dark:bg-gray-800/50'"
                                    @dragover.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="onDrop"
                                >
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-xl transition"
                                        :class="isDragging ? 'bg-[#087ab1] text-white' : 'bg-[#087ab1]/10 text-[#087ab1]'"
                                    >
                                        <Upload class="h-6 w-6" />
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Arrastra tu archivo aquí
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            o haz clic para seleccionar · .xlsx, .xls
                                        </p>
                                    </div>
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                        accept=".xlsx,.xls"
                                        @change="onFileChange"
                                    />
                                </div>

                                <p v-if="form.errors.file" class="text-xs text-red-500">
                                    {{ form.errors.file }}
                                </p>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50/70 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/50">
                            <p class="text-xs text-gray-400">
                                Máximo 10 MB por archivo
                            </p>
                            <div class="flex gap-3">
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
                                    :disabled="form.processing || !form.file"
                                    @click="submit"
                                >
                                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    <Upload v-else class="h-4 w-4" />
                                    {{ form.processing ? 'Importando...' : 'Importar' }}
                                </button>
                            </div>
                        </div>

                    </DialogPanel>
                </TransitionChild>
            </div>

        </Dialog>
    </TransitionRoot>
</template>
