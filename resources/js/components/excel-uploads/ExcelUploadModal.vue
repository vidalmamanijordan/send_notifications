<script setup lang="ts">
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { CalendarDays, FileSpreadsheet, Lock, MapPin, Upload, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

/* =========================
Props
========================= */
const props = defineProps<{
    show: boolean;
    activePeriod: { id: number; name: string } | null;
    campus: { id: number; name: string }[];
}>();

const emit = defineEmits(['close', 'success']);

/* =========================
Form
========================= */
const form = useForm({
    academic_period_id: '' as string | number,
    campus_id: '' as string | number,
    file: null as File | null,
});

/* =========================
File drag & drop
========================= */
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

/* =========================
Live preview values
========================= */
const selectedPeriodName = computed(() =>
    props.activePeriod?.name ?? 'Sin periodo activo',
);

const selectedCampusName = computed(() => {
    if (!form.campus_id) return 'Sin campus';
    return props.campus.find((c) => c.id == form.campus_id)?.name ?? 'Sin campus';
});

/* =========================
Pre-cargar periodo activo y reset on close
========================= */
watch(
    () => props.show,
    (val) => {
        if (val) {
            form.academic_period_id = props.activePeriod?.id ?? '';
        } else {
            form.reset();
            form.clearErrors();
            isDragging.value = false;
        }
    },
);

/* =========================
Submit
========================= */
const submit = () => {
    form.post(route('admin.excel-uploads.store'), {
        forceFormData: true,
        onSuccess: () => emit('success'),
    });
};
</script>

<template>
    <TransitionRoot appear :show="show" as="template">
        <Dialog @close="emit('close')" class="relative z-50">

            <!-- Backdrop -->
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
                    <DialogPanel class="w-full overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">

                        <!-- HEADER con preview en vivo -->
                        <div class="relative overflow-hidden bg-linear-to-br from-[#087ab1] to-[#68c8fb] px-6 py-6">
                            <!-- Círculos decorativos -->
                            <div class="pointer-events-none absolute -top-12 -right-12 h-52 w-52 rounded-full bg-white/8" />
                            <div class="pointer-events-none absolute right-40 -bottom-8 h-36 w-36 rounded-full bg-white/5" />
                            <div class="pointer-events-none absolute bottom-0 left-1/3 h-24 w-24 rounded-full bg-white/5" />
                            <!-- Líneas diagonales decorativas -->
                            <div class="pointer-events-none absolute -top-2 right-28 h-[160%] w-px rotate-22 rounded-full bg-white/20" />
                            <div class="pointer-events-none absolute -top-2 right-20 h-[160%] w-px rotate-22 rounded-full bg-white/14" />
                            <div class="pointer-events-none absolute -top-2 right-12 h-[160%] w-px rotate-22 rounded-full bg-white/8" />

                            <div class="relative flex items-stretch gap-5">
                                <!-- Título -->
                                <div class="flex flex-1 flex-col justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                            <FileSpreadsheet class="h-5 w-5 text-white" />
                                        </div>
                                        <div>
                                            <DialogTitle class="text-base font-bold text-white">
                                                Subir Reporte Excel
                                            </DialogTitle>
                                            <p class="text-xs text-[#68c8fb]">
                                                Selecciona el periodo, campus y archivo .xlsx o .xls
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1 transition-all duration-300"
                                            :class="form.file
                                                ? 'bg-emerald-500/20 text-emerald-100 ring-emerald-400/30'
                                                : 'bg-gray-500/20 text-gray-300 ring-gray-400/30'"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full transition-colors duration-300"
                                                :class="form.file ? 'animate-pulse bg-emerald-400' : 'bg-gray-400'"
                                            />
                                            {{ form.file ? 'Archivo listo para subir' : 'Sin archivo seleccionado' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Vista previa en vivo -->
                                <div class="w-56 shrink-0 overflow-hidden rounded-xl bg-white/10 ring-1 ring-white/20">
                                    <div class="flex items-center gap-1.5 border-b border-white/10 bg-black/10 px-3 py-2">
                                        <span class="h-2 w-2 rounded-full bg-red-400/80" />
                                        <span class="h-2 w-2 rounded-full bg-yellow-400/80" />
                                        <span class="h-2 w-2 rounded-full bg-green-400/80" />
                                        <span class="ml-2 text-xs text-white/40">vista previa</span>
                                    </div>
                                    <div class="space-y-2.5 p-3">
                                        <div class="flex items-center gap-1.5">
                                            <CalendarDays class="h-3 w-3 shrink-0 text-[#68c8fb]" />
                                            <p class="truncate text-xs font-semibold text-white">
                                                {{ selectedPeriodName }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <MapPin class="h-3 w-3 shrink-0 text-[#68c8fb]" />
                                            <p class="truncate text-xs text-white/80">
                                                {{ selectedCampusName }}
                                            </p>
                                        </div>
                                        <div class="h-px bg-white/10" />
                                        <div class="flex items-center gap-1.5">
                                            <FileSpreadsheet class="h-3 w-3 shrink-0 text-[#68c8fb]" />
                                            <p class="truncate text-xs leading-relaxed text-white/60">
                                                {{ form.file ? form.file.name : 'Sin archivo...' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div class="max-h-[60vh] overflow-y-auto">
                            <div class="space-y-5 p-6">

                                <!-- Fila: Periodo + Campus -->
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Periodo (solo lectura) -->
                                    <div class="space-y-1.5">
                                        <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                            <CalendarDays class="h-3.5 w-3.5 text-[#68c8fb]" />
                                            Periodo Académico
                                        </label>
                                        <div class="flex items-center gap-2.5 rounded-xl border border-[#087ab1]/30 bg-[#087ab1]/5 px-3.5 py-2.5">
                                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500" />
                                            <span class="flex-1 text-sm font-medium text-gray-800 dark:text-gray-100">
                                                {{ activePeriod?.name ?? 'Sin periodo activo' }}
                                            </span>
                                            <Lock class="h-3.5 w-3.5 shrink-0 text-gray-400" />
                                        </div>
                                        <p v-if="!activePeriod" class="text-xs text-amber-500">
                                            No hay un periodo académico activo configurado.
                                        </p>
                                    </div>

                                    <!-- Campus -->
                                    <div class="space-y-1.5">
                                        <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                            <MapPin class="h-3.5 w-3.5 text-[#68c8fb]" />
                                            Campus
                                            <span class="ml-auto font-normal text-red-400 normal-case">Requerido</span>
                                        </label>
                                        <select
                                            v-model="form.campus_id"
                                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                            :class="{ 'border-red-300 bg-red-50': form.errors.campus_id }"
                                        >
                                            <option value="">Seleccione...</option>
                                            <option
                                                v-for="c in campus"
                                                :key="c.id"
                                                :value="c.id"
                                            >
                                                {{ c.name }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors.campus_id" class="text-xs text-red-500">
                                            {{ form.errors.campus_id }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Separador -->
                                <div class="relative flex items-center gap-3">
                                    <div class="h-px flex-1 bg-gray-100" />
                                    <span class="rounded-full border border-gray-100 bg-white px-3 py-0.5 text-xs font-medium tracking-wider text-gray-400 uppercase">
                                        Archivo Excel
                                    </span>
                                    <div class="h-px flex-1 bg-gray-100" />
                                </div>

                                <!-- Zona de archivo -->
                                <div class="space-y-1.5">
                                    <label class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                        <FileSpreadsheet class="h-3.5 w-3.5 text-[#68c8fb]" />
                                        Archivo
                                        <span class="ml-auto font-normal text-red-400 normal-case">Requerido</span>
                                    </label>

                                    <!-- Preview cuando hay archivo -->
                                    <div
                                        v-if="form.file"
                                        class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100">
                                                <FileSpreadsheet class="h-5 w-5 text-emerald-600" />
                                            </div>
                                            <div>
                                                <p class="truncate max-w-[240px] text-sm font-semibold text-gray-800">
                                                    {{ form.file.name }}
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    {{ formatFileSize(form.file.size) }}
                                                </p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeFile"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-100 hover:text-red-500"
                                        >
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <!-- Zona drag & drop -->
                                    <div
                                        v-else
                                        class="relative flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed p-10 transition-colors cursor-pointer"
                                        :class="isDragging
                                            ? 'border-[#087ab1] bg-[#087ab1]/5'
                                            : 'border-gray-200 bg-gray-50 hover:border-[#087ab1]/50 hover:bg-[#087ab1]/5'"
                                        @dragover.prevent="isDragging = true"
                                        @dragleave.prevent="isDragging = false"
                                        @drop.prevent="onDrop"
                                        @click="fileInput?.click()"
                                    >
                                        <div
                                            class="flex h-12 w-12 items-center justify-center rounded-xl transition"
                                            :class="isDragging ? 'bg-[#087ab1] text-white' : 'bg-[#087ab1]/10 text-[#087ab1]'"
                                        >
                                            <Upload class="h-6 w-6" />
                                        </div>
                                        <div class="text-center">
                                            <p class="text-sm font-medium text-gray-700">
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
                        </div>

                        <!-- FOOTER -->
                        <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50/70 px-6 py-4">
                            <p class="text-xs text-gray-400">
                                <span class="text-red-400">*</span> Los campos marcados son requeridos
                            </p>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                                    @click="emit('close')"
                                >
                                    Cerrar
                                </button>
                                <button
                                    type="button"
                                    class="flex items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#087ab1]/30 transition-all hover:opacity-90 hover:shadow-lg hover:shadow-[#087ab1]/40 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="form.processing || !activePeriod"
                                    @click="submit"
                                >
                                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    <Upload v-else class="h-4 w-4" />
                                    {{ form.processing ? 'Subiendo...' : 'Subir archivo' }}
                                </button>
                            </div>
                        </div>

                    </DialogPanel>
                </TransitionChild>
            </div>

        </Dialog>
    </TransitionRoot>
</template>
