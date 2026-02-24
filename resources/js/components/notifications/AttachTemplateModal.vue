<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

interface Template {
    id: number;
    name: string;
}

const props = defineProps<{
    show: boolean;
    batchId: number | null;
    templates: Template[];
}>();

const emit = defineEmits(['close']);
const selectedTemplate = ref<number | null>(null);

const attachTemplate = () => {
    if (!selectedTemplate.value || !props.batchId) return;

    router.post(
        route('admin.notification-batches.attach-template', props.batchId),
        {
            notification_template_id: selectedTemplate.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        }
    );
};
</script>

<template>
    <div v-if="show"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">

        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">

            <h2 class="mb-4 text-lg font-semibold">
                Enlazar plantilla al lote
            </h2>

            <select
                v-model="selectedTemplate"
                class="w-full rounded border px-3 py-2 text-sm"
            >
                <option value="">Seleccione una plantilla</option>
                <option
                    v-for="template in templates"
                    :key="template.id"
                    :value="template.id"
                >
                    {{ template.name }}
                </option>
            </select>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    @click="$emit('close')"
                    class="rounded bg-gray-300 px-4 py-2 text-sm hover:bg-gray-400">
                    Cancelar
                </button>

                <button
                    @click="attachTemplate"
                    class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">
                    Asociar
                </button>
            </div>

        </div>
    </div>
</template>