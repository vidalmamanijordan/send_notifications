<script setup lang="ts">
import { Building2 } from 'lucide-vue-next';
import { computed, ref, toRef, watch } from 'vue';

const props = defineProps<{
    show: boolean;
    editing: boolean;
    form: any;
    signaturePath?: string | null;
}>();

const emit = defineEmits(['close', 'submit']);

/*
✔ referencia reactiva correcta del form
*/
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
    <div
        v-if="props.show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
        <div class="w-full max-w-5xl rounded-xl bg-white p-6 shadow-xl">
            <!-- HEADER -->
            <div class="mb-4 flex items-center gap-2">
                <Building2 class="h-5 w-5 text-indigo-600" />
                <h2 class="text-lg font-semibold">
                    {{ props.editing ? 'Editar Oficina' : 'Nueva Oficina' }}
                </h2>
            </div>

            <!-- FORM -->
            <div class="grid grid-cols-2 gap-6">
                <!-- COLUMNA 1 -->
                <div class="space-y-4">
                    <!-- NOMBRE -->
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Nombre
                        </label>

                        <input
                            v-model="form.name"
                            class="w-full border-b border-gray-300 px-0 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                        />

                        <p v-if="form.errors.name" class="text-xs text-red-500">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- CODIGO -->
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Código
                        </label>

                        <input
                            v-model="form.code"
                            class="w-full border-b border-gray-300 px-0 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                        />

                        <p v-if="form.errors.code" class="text-xs text-red-500">
                            {{ form.errors.code }}
                        </p>
                    </div>

                    <!-- EMAIL -->
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Correo
                        </label>

                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full border-b border-gray-300 px-0 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                        />

                        <p
                            v-if="form.errors.email"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- CC EMAIL -->
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Correo CC
                        </label>

                        <input
                            v-model="form.cc_email"
                            type="email"
                            class="w-full border-b border-gray-300 px-0 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                        />

                        <p
                            v-if="form.errors.cc_email"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.cc_email }}
                        </p>
                    </div>

                    <!-- LEVEL -->
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Nivel
                        </label>

                        <input
                            v-model="form.level"
                            type="number"
                            min="1"
                            class="w-full border-b border-gray-300 px-0 py-2 text-sm focus:border-indigo-500 focus:outline-none"
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
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Firma
                        </label>

                        <input
                            type="file"
                            accept="image/*"
                            @change="onFileChange"
                            class="w-full border-b border-gray-300 px-0 py-2 text-sm"
                        />

                        <p
                            v-if="form.errors.signature"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.signature }}
                        </p>

                        <!-- PREVIEW -->
                        <div v-if="signaturePreview" class="mt-2">
                            <span class="text-xs text-gray-500">
                                Firma actual
                            </span>

                            <img
                                :src="signaturePreview"
                                class="mt-1 max-h-32 rounded border"
                            />

                            <p
                                v-if="props.signaturePath"
                                class="mt-1 text-xs text-gray-400"
                            >
                                {{ props.signaturePath }}
                            </p>
                        </div>
                    </div>

                    <!-- SWITCH ACTIVO -->
                    <div class="flex items-center gap-4 pt-2">
                        <button
                            type="button"
                            @click="toggleStatus"
                            class="relative inline-flex h-8 w-16 items-center rounded-full transition-colors"
                            :class="
                                form.is_active ? 'bg-indigo-600' : 'bg-gray-300'
                            "
                        >
                            <span
                                :class="[
                                    'inline-block h-6 w-6 transform rounded-full bg-white shadow-md transition-transform',
                                    form.is_active
                                        ? 'translate-x-8'
                                        : 'translate-x-1',
                                ]"
                            />
                        </button>

                        <span class="text-sm font-medium text-black">
                            {{ statusText }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- BOTONES -->
            <div class="flex justify-end gap-2 pt-6">
                <button
                    @click="$emit('close')"
                    class="rounded-lg bg-gray-400 px-4 py-2 text-sm text-white hover:bg-gray-500"
                >
                    Cancelar
                </button>

                <button
                    @click="submitForm"
                    :disabled="form.processing"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    {{ form.processing ? 'Guardando...' : 'Guardar' }}
                </button>
            </div>
        </div>
    </div>
</template>
