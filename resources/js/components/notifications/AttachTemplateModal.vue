<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { onBeforeUnmount, ref, watch } from 'vue';

interface Template {
    id: number;
    name: string;
}

const props = defineProps<{
    show: boolean;
    batchId: number | null;
    currentTemplateId: number | null;
}>();

const emit = defineEmits(['close']);

const selectedTemplate = ref<number | null>(null);
const templates = ref<Template[]>([]);
const loading = ref(false);
const updated = ref(false);

let intervalId: ReturnType<typeof setInterval> | null = null;

/* =========================
   API CALL CENTRALIZADA
========================= */
const fetchTemplates = async (): Promise<Template[]> => {
    const url = route('admin.notification-templates.list');
    const { data } = await axios.get(url);
    return data ?? [];
};

/* =========================
   CARGA INICIAL
========================= */
const loadTemplates = async () => {
    try {
        loading.value = true;
        templates.value = await fetchTemplates();
    } catch (error) {
        console.error('Error cargando plantillas', error);
    } finally {
        loading.value = false;
    }
};

/* =========================
   REFRESH SIN PERDER SELECCIÓN
========================= */
const refreshTemplates = async () => {
    try {
        const newTemplates = await fetchTemplates();

        // limpiar selección si ya no existe
        if (
            selectedTemplate.value &&
            !newTemplates.some((t) => t.id === selectedTemplate.value)
        ) {
            selectedTemplate.value = null;
        }

        // detectar cambios
        const changed =
            newTemplates.length !== templates.value.length ||
            newTemplates.some((t, i) => {
                const old = templates.value[i];
                return !old || t.id !== old.id || t.name !== old.name;
            });

        if (changed) {
            templates.value = [...newTemplates];
            updated.value = true;
            setTimeout(() => (updated.value = false), 2000);
        }
    } catch (error) {
        console.error('Error refrescando plantillas', error);
    }
};

/* =========================
   WATCH MODAL
========================= */
watch(
    () => props.show,
    async (open) => {
        if (!open) {
            stopAutoRefresh();
            return;
        }

        selectedTemplate.value = props.currentTemplateId ?? null;
        await loadTemplates();
        startAutoRefresh();
    },
);

/* =========================
   AUTO REFRESH
========================= */
const startAutoRefresh = () => {
    if (intervalId) return;
    intervalId = setInterval(refreshTemplates, 5000);
};

const stopAutoRefresh = () => {
    if (!intervalId) return;
    clearInterval(intervalId);
    intervalId = null;
};

/* =========================
   LIMPIAR AL DESTRUIR
========================= */
onBeforeUnmount(() => {
    stopAutoRefresh();
});

/* =========================
   ASOCIAR
========================= */
const attachTemplate = () => {
    if (!selectedTemplate.value || !props.batchId) return;

    router.patch(
        route('admin.notification-batches.attach-template', props.batchId),
        {
            notification_template_id: selectedTemplate.value,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                emit('close');
            },

            onError: (errors) => {
                if (errors && Object.keys(errors).length > 0) {
                    alert(Object.values(errors)[0]);
                }
            },
        },
    );
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h2 class="mb-4 text-lg font-semibold">
                Enlazar plantilla al lote
            </h2>

            <!-- LOADING -->
            <div v-if="loading" class="mb-3 text-sm text-gray-500">
                Cargando plantillas...
            </div>

            <!-- ACTUALIZADO -->
            <div v-if="updated" class="mb-2 text-xs text-green-600">
                ✔ Lista actualizada
            </div>

            <!-- SELECT -->
            <select
                v-model="selectedTemplate"
                class="w-full rounded border px-3 py-2 text-sm"
            >
                <option :value="null">Seleccione una plantilla</option>

                <option
                    v-for="template in templates"
                    :key="template.id"
                    :value="template.id"
                >
                    {{ template.name }}
                </option>
            </select>

            <!-- BOTONES -->
            <div class="mt-6 flex justify-end gap-2">
                <button
                    @click="$emit('close')"
                    class="rounded bg-gray-300 px-4 py-2 text-sm hover:bg-gray-400"
                >
                    Cancelar
                </button>

                <button
                    @click="attachTemplate"
                    class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700"
                >
                    Asociar
                </button>
            </div>
        </div>
    </div>
</template>
