<script setup lang="ts">
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { Building2 } from 'lucide-vue-next';
import { computed, ref, toRef, watch } from 'vue';

const props = defineProps<{
    show: boolean;
    editing: boolean;
    form: any;
    signaturePath?: string | null;
}>();

const emit = defineEmits(['close', 'submit']);

const form = toRef(props, 'form');

const signaturePreview = ref<string | null>(null);

watch(
    () => [form.value.signature, props.signaturePath],
    ([value, path]) => {
        if (value instanceof File) {
            signaturePreview.value = URL.createObjectURL(value);
            return;
        }

        if (path) {
            signaturePreview.value = `/storage/${path}`;
            return;
        }

        signaturePreview.value = null;
    },
    { immediate: true },
);

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (!target.files || !target.files.length) return;

    const file = target.files[0];

    form.value.signature = file;

    signaturePreview.value = URL.createObjectURL(file);
};

const toggleStatus = () => {
    form.value.is_active = !form.value.is_active;
};

const submitForm = () => {
    emit('submit', form.value);
};

const statusText = computed(() =>
    form.value.is_active ? 'Activo' : 'Inactivo',
);
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
                    class="relative w-full max-w-3xl"
                >
                    <DialogPanel
                        class="w-full overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                    >
                        <!-- HEADER -->
                        <div
                            class="relative overflow-hidden bg-linear-to-br from-[#087ab1] to-[#68c8fb] px-6 py-6"
                        >
                            <!-- Círculos decorativos -->
                            <div
                                class="pointer-events-none absolute -top-12 -right-12 h-52 w-52 rounded-full bg-white/8"
                            />
                            <div
                                class="pointer-events-none absolute right-40 -bottom-8 h-36 w-36 rounded-full bg-white/5"
                            />
                            <div
                                class="pointer-events-none absolute bottom-0 left-1/3 h-24 w-24 rounded-full bg-white/5"
                            />
                            <!-- Líneas diagonales decorativas -->
                            <div
                                class="pointer-events-none absolute -top-2 right-28 h-[160%] w-px rotate-22 rounded-full bg-white/20"
                            />
                            <div
                                class="pointer-events-none absolute -top-2 right-20 h-[160%] w-px rotate-22 rounded-full bg-white/14"
                            />
                            <div
                                class="pointer-events-none absolute -top-2 right-12 h-[160%] w-px rotate-22 rounded-full bg-white/8"
                            />

                            <div class="relative flex items-center gap-3">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25"
                                >
                                    <Building2 class="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <DialogTitle
                                        class="text-base font-bold text-white"
                                    >
                                        {{
                                            props.editing
                                                ? 'Editar Oficina'
                                                : 'Nueva Oficina'
                                        }}
                                    </DialogTitle>
                                    <p class="text-xs text-[#68c8fb]">
                                        {{
                                            props.editing
                                                ? 'Modifica los datos de la oficina'
                                                : 'Completa los campos para registrar una nueva oficina'
                                        }}
                                    </p>
                                </div>

                                <!-- Badge estado -->
                                <span
                                    :class="[
                                        'ml-auto inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1 transition-all duration-300',
                                        form.is_active
                                            ? 'bg-emerald-500/20 text-emerald-100 ring-emerald-400/30'
                                            : 'bg-gray-500/20 text-gray-300 ring-gray-400/30',
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'h-1.5 w-1.5 rounded-full transition-colors duration-300',
                                            form.is_active
                                                ? 'animate-pulse bg-emerald-400'
                                                : 'bg-gray-400',
                                        ]"
                                    />
                                    {{ statusText }}
                                </span>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div class="max-h-[65vh] overflow-y-auto">
                            <div class="grid grid-cols-2 gap-6 p-6">
                                <!-- COLUMNA 1 -->
                                <div class="space-y-4">
                                    <!-- NOMBRE -->
                                    <div class="space-y-1.5">
                                        <label
                                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                        >
                                            Nombre
                                            <span
                                                class="ml-auto font-normal text-red-400 normal-case"
                                                >Requerido</span
                                            >
                                        </label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            placeholder="Ej: Dirección Académica"
                                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                            :class="{
                                                'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100':
                                                    form.errors.name,
                                            }"
                                        />
                                        <p
                                            v-if="form.errors.name"
                                            class="text-xs text-red-500"
                                        >
                                            {{ form.errors.name }}
                                        </p>
                                    </div>

                                    <!-- CÓDIGO -->
                                    <div class="space-y-1.5">
                                        <label
                                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                        >
                                            Código
                                            <span
                                                class="ml-auto font-normal text-red-400 normal-case"
                                                >Requerido</span
                                            >
                                        </label>
                                        <input
                                            v-model="form.code"
                                            type="text"
                                            placeholder="Ej: DIR-ACAD"
                                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                            :class="{
                                                'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100':
                                                    form.errors.code,
                                            }"
                                        />
                                        <p
                                            v-if="form.errors.code"
                                            class="text-xs text-red-500"
                                        >
                                            {{ form.errors.code }}
                                        </p>
                                    </div>

                                    <!-- CORREO -->
                                    <div class="space-y-1.5">
                                        <label
                                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                        >
                                            Correo
                                            <span
                                                class="ml-auto font-normal text-red-400 normal-case"
                                                >Requerido</span
                                            >
                                        </label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            placeholder="oficina@universidad.edu.pe"
                                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                            :class="{
                                                'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100':
                                                    form.errors.email,
                                            }"
                                        />
                                        <p
                                            v-if="form.errors.email"
                                            class="text-xs text-red-500"
                                        >
                                            {{ form.errors.email }}
                                        </p>
                                    </div>

                                    <!-- CORREO CC -->
                                    <div class="space-y-1.5">
                                        <label
                                            class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                        >
                                            Correo CC
                                            <span
                                                class="ml-1 font-normal text-gray-400 normal-case"
                                                >(opcional)</span
                                            >
                                        </label>
                                        <input
                                            v-model="form.cc_email"
                                            type="email"
                                            placeholder="copia@universidad.edu.pe"
                                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                            :class="{
                                                'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100':
                                                    form.errors.cc_email,
                                            }"
                                        />
                                        <p
                                            v-if="form.errors.cc_email"
                                            class="text-xs text-red-500"
                                        >
                                            {{ form.errors.cc_email }}
                                        </p>
                                    </div>

                                    <!-- NIVEL -->
                                    <div class="space-y-1.5">
                                        <label
                                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                        >
                                            Nivel
                                            <span
                                                class="ml-auto font-normal text-red-400 normal-case"
                                                >Requerido</span
                                            >
                                        </label>
                                        <input
                                            v-model="form.level"
                                            type="number"
                                            min="1"
                                            placeholder="1"
                                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                            :class="{
                                                'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100':
                                                    form.errors.level,
                                            }"
                                        />
                                        <p
                                            v-if="form.errors.level"
                                            class="text-xs text-red-500"
                                        >
                                            {{ form.errors.level }}
                                        </p>
                                    </div>
                                </div>

                                <!-- COLUMNA 2 -->
                                <div class="space-y-4">
                                    <!-- FIRMA -->
                                    <div class="space-y-1.5">
                                        <label
                                            class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                        >
                                            Firma
                                            <span
                                                class="ml-1 font-normal text-gray-400 normal-case"
                                                >(imagen)</span
                                            >
                                        </label>
                                        <label
                                            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center transition hover:border-[#087ab1]/40 hover:bg-[#087ab1]/5"
                                        >
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-[#087ab1]/10"
                                            >
                                                <Building2
                                                    class="h-5 w-5 text-[#087ab1]"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-xs font-medium text-gray-600"
                                                >
                                                    Haz clic para seleccionar
                                                </p>
                                                <p
                                                    class="text-xs text-gray-400"
                                                >
                                                    PNG, JPG, WEBP
                                                </p>
                                            </div>
                                            <input
                                                type="file"
                                                accept="image/*"
                                                @change="onFileChange"
                                                class="hidden"
                                            />
                                        </label>
                                        <p
                                            v-if="form.errors.signature"
                                            class="text-xs text-red-500"
                                        >
                                            {{ form.errors.signature }}
                                        </p>

                                        <!-- PREVIEW -->
                                        <div
                                            v-if="signaturePreview"
                                            class="overflow-hidden rounded-xl border border-gray-200 bg-white p-2 shadow-sm"
                                        >
                                            <p
                                                class="mb-1.5 text-xs font-medium text-gray-500"
                                            >
                                                Firma actual
                                            </p>
                                            <img
                                                :src="signaturePreview"
                                                class="max-h-28 w-full rounded-lg object-contain"
                                            />
                                            <p
                                                v-if="props.signaturePath"
                                                class="mt-1 truncate text-xs text-gray-400"
                                            >
                                                {{ props.signaturePath }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- ESTADO -->
                                    <div class="space-y-1.5">
                                        <label
                                            class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                        >
                                            Estado
                                        </label>
                                        <div
                                            class="flex items-center gap-3 rounded-xl border border-[#68c8fb]/30 bg-[#68c8fb]/10 px-4 py-3"
                                        >
                                            <button
                                                type="button"
                                                @click="toggleStatus"
                                                :class="[
                                                    'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:ring-2 focus:ring-[#087ab1] focus:ring-offset-1 focus:outline-none',
                                                    form.is_active
                                                        ? 'bg-[#087ab1]'
                                                        : 'bg-gray-200',
                                                ]"
                                            >
                                                <span
                                                    :class="[
                                                        'inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-md transition-transform',
                                                        form.is_active
                                                            ? 'translate-x-[18px]'
                                                            : 'translate-x-[3px]',
                                                    ]"
                                                />
                                            </button>
                                            <span
                                                class="text-sm font-medium text-gray-700"
                                            >
                                                {{ statusText }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div
                            class="flex items-center justify-between border-t border-gray-100 bg-gray-50/70 px-6 py-4"
                        >
                            <p class="text-xs text-gray-400">
                                <span class="text-red-400">*</span> Los campos
                                marcados son requeridos
                            </p>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                                    @click="emit('close')"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    class="flex items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#087ab1]/30 transition-all hover:opacity-90 hover:shadow-lg hover:shadow-[#087ab1]/40 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="form.processing"
                                    @click="submitForm"
                                >
                                    <svg
                                        v-if="form.processing"
                                        class="h-4 w-4 animate-spin"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        />
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                        />
                                    </svg>
                                    {{
                                        form.processing
                                            ? 'Guardando...'
                                            : props.editing
                                              ? 'Actualizar oficina'
                                              : 'Crear oficina'
                                    }}
                                </button>
                            </div>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
