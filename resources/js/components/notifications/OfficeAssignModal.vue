<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onUnmounted, ref, watch } from 'vue';

interface Office {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    show: boolean;
    batchId: number | null;
    offices: Office[];
    currentOfficeId: number | null;
}>();

const emit = defineEmits(['close']);

const selectedOffice = ref<number | null>(null);

let refreshInterval: any = null;

const startRefreshingOffices = () => {
    if (refreshInterval) return;
    refreshInterval = setInterval(() => {
        router.reload({
            only: ['offices'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 3000); // cada 3 segundos
};

const stopRefreshingOffices = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
};

watch(
    () => props.currentOfficeId,
    (val) => {
        selectedOffice.value = val;
    },
    { immediate: true },
);

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            // SINCRONIZA EL RADIO CON LA OFICINA ACTUAL
            selectedOffice.value = props.currentOfficeId ?? null;
            startRefreshingOffices();
        } else {
            stopRefreshingOffices();
        }
    },
);

onUnmounted(() => {
    stopRefreshingOffices();
});

const save = () => {
    if (!props.batchId || !selectedOffice.value) return;

    router.patch(
        route('admin.notification-batches.assign-office', props.batchId),
        {
            office_id: selectedOffice.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({
                    only: ['batches'],
                    preserveScroll: true,
                    preserveState: true,
                });

                emit('close');
            },
        },
    );
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    >
        <div class="w-[500px] rounded-2xl bg-white p-6 shadow-2xl">
            <!-- HEADER -->
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        Seleccionar Oficina
                    </h2>

                    <p class="text-xs text-gray-500">
                        Elige la oficina que enviará las notificaciones
                    </p>
                </div>
            </div>

            <!-- LISTA -->
            <div class="max-h-[420px] space-y-3 overflow-y-auto pr-1">
                <label
                    v-for="office in offices"
                    :key="office.id"
                    class="flex cursor-pointer items-center gap-4 rounded-xl border p-4 transition hover:border-indigo-400 hover:bg-indigo-50"
                    :class="
                        selectedOffice === office.id
                            ? 'border-indigo-500 bg-indigo-50'
                            : ''
                    "
                >
                    <!-- RADIO -->
                    <input
                        type="radio"
                        :value="office.id"
                        v-model="selectedOffice"
                        class="h-4 w-4 text-indigo-600"
                    />

                    <!-- ICONO -->
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600"
                    >
                        🏢
                    </div>

                    <!-- INFO -->
                    <div class="flex flex-col leading-tight">
                        <span class="text-sm font-medium text-gray-800">
                            {{ office.name }}
                        </span>

                        <span class="text-xs text-gray-500">
                            {{ office.email }}
                        </span>
                    </div>
                </label>
            </div>

            <!-- FOOTER -->
            <div class="mt-6 flex justify-end gap-3">
                <button
                    @click="$emit('close')"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-100"
                >
                    Cancelar
                </button>

                <button
                    @click="save"
                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700"
                >
                    Guardar Oficina
                </button>
            </div>
        </div>
    </div>
</template>
