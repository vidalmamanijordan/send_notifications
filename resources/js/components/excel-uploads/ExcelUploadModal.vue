<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

/* =========================
Props
========================= */
const props = defineProps<{
    show: boolean;
    academicPeriods: { id: number; name: string }[];
    campus: { id: number; name: string }[];
}>();

const emit = defineEmits(['close', 'success']);

/* =========================
Form
========================= */
const form = useForm({
    academic_period_id: '',
    campus_id: '',
    file: null as File | null,
});

/* =========================
Reset
========================= */
watch(() => props.show, (val) => {
    if (!val) {
        form.reset();
        form.clearErrors();
    }
});

/* =========================
Submit
========================= */
const submit = () => {
    form.post(route('admin.excel-uploads.store'), {
        forceFormData: true,
        onSuccess: () => {
            emit('success');
        },
    });
};

/* =========================
Close
========================= */
const close = () => {
    emit('close');
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
        <div class="w-full max-w-lg rounded-xl bg-white shadow-lg p-6 space-y-4">

            <!-- HEADER -->
            <div class="flex justify-between items-center border-b pb-3">
                <h2 class="text-lg font-semibold">
                    Subir Reporte Excel
                </h2>

                <button
                    @click="close"
                    class="text-gray-500 hover:text-gray-800"
                >
                    ✕
                </button>
            </div>

            <!-- FORM -->
            <form @submit.prevent="submit" class="space-y-4">

                <!-- Periodo -->
                <div>
                    <label class="block text-sm font-medium">
                        Periodo Académico
                    </label>
                    <select
                        v-model="form.academic_period_id"
                        class="w-full mt-1 rounded-md border-gray-300"
                    >
                        <option value="">Seleccione...</option>
                        <option
                            v-for="p in academicPeriods"
                            :key="p.id"
                            :value="p.id"
                        >
                            {{ p.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.academic_period_id" class="text-red-500 text-xs">
                        {{ form.errors.academic_period_id }}
                    </p>
                </div>

                <!-- Campus -->
                <div>
                    <label class="block text-sm font-medium">
                        Campus
                    </label>
                    <select
                        v-model="form.campus_id"
                        class="w-full mt-1 rounded-md border-gray-300"
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
                    <p v-if="form.errors.campus_id" class="text-red-500 text-xs">
                        {{ form.errors.campus_id }}
                    </p>
                </div>

                <!-- Archivo -->
                <div>
                    <label class="block text-sm font-medium">
                        Archivo Excel
                    </label>
                    <input
                        type="file"
                        @change="e => form.file = e.target.files[0]"
                        class="w-full mt-1"
                        accept=".xlsx,.xls"
                    />
                    <p v-if="form.errors.file" class="text-red-500 text-xs">
                        {{ form.errors.file }}
                    </p>
                </div>

                <!-- ACTIONS -->
                <div class="flex justify-end gap-2 pt-4">
                    <button
                        type="button"
                        @click="close"
                        class="px-4 py-2 rounded-md border"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700"
                        :disabled="form.processing"
                    >
                        Subir
                    </button>
                </div>

            </form>
        </div>
    </div>
</template>
