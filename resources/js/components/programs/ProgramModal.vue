<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, watch } from 'vue';

/* =========================
   Props & Emits
========================= */
interface Program {
    id: number;
    name: string;
    code: string;
    level: 'undergraduate' | 'postgraduate';
}

const props = defineProps<{
    show: boolean;
    mode: 'create' | 'edit';
    program: Program | null;
}>();

const emit = defineEmits(['close', 'success']);

/* =========================
   Form
========================= */
const form = useForm({
    name: '',
    code: '',
    level: 'undergraduate',
});

/* =========================
   Computed
========================= */
const isEdit = computed(() => props.mode === 'edit');

const modalTitle = computed(() =>
    isEdit.value ? 'Editar Programa' : 'Nuevo Programa',
);

const submitLabel = computed(() => (isEdit.value ? 'Actualizar' : 'Guardar'));

/* =========================
   Watch
========================= */
watch(
    () => props.program,
    (program) => {
        if (program && isEdit.value) {
            form.name = program.name;
            form.code = program.code;
            form.level = program.level;
        } else {
            form.reset();
        }
    },
    { immediate: true },
);

/* =========================
   Actions
========================= */
const submit = () => {
    if (isEdit.value && props.program) {
        form.put(route('admin.programs.update', props.program.id), {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post(route('admin.programs.store'), {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    }
};

const close = () => {
    form.reset();
    emit('close');
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-900"
        >
            <!-- HEADER -->
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {{ modalTitle }}
                </h2>

                <button
                    @click="close"
                    class="text-gray-400 hover:text-gray-600"
                >
                    ✕
                </button>
            </div>

            <!-- FORM -->
            <form @submit.prevent="submit" class="space-y-4">
                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-medium">Nombre</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Código -->
                <div>
                    <label class="block text-sm font-medium">Código</label>
                    <input
                        v-model="form.code"
                        type="text"
                        class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.code" class="mt-1 text-xs text-red-600">
                        {{ form.errors.code }}
                    </p>
                </div>

                <!-- Nivel -->
                <div>
                    <label class="block text-sm font-medium">
                        Nivel Académico
                    </label>
                    <select
                        v-model="form.level"
                        class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="undergraduate">Pregrado</option>
                        <option value="postgraduate">Posgrado</option>
                    </select>

                    <p
                        v-if="form.errors.level"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ form.errors.level }}
                    </p>
                </div>

                <!-- ACTIONS -->
                <div class="flex justify-end gap-2 pt-4">
                    <button
                        type="button"
                        @click="close"
                        class="rounded-md border px-4 py-2 text-sm hover:bg-gray-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ submitLabel }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>


<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
