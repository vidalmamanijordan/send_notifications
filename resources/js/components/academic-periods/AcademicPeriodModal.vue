<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Props {
    show: boolean;
    mode: 'create' | 'edit';
    academicPeriod?: {
        id: number;
        code: string;
        name: string;
        start_date: string;
        end_date: string;
        status: 'active' | 'closed';
    } | null;
}

const props = defineProps<Props>();
const emit = defineEmits(['close']);

const form = useForm({
    code: '',
    name: '',
    start_date: '',
    end_date: '',
    status: 'active',
});

watch(
    () => props.academicPeriod,
    (value) => {
        if (value && props.mode === 'edit') {
            form.code = value.code;
            form.name = value.name;
            form.start_date = value.start_date;
            form.end_date = value.end_date;
            form.status = value.status;
        }

        if (props.mode === 'create') {
            form.reset();
            form.status = 'active';
        }
    },
    { immediate: true },
);

const modalTitle = computed(() =>
    props.mode === 'create'
        ? 'Nuevo Periodo Académico'
        : 'Editar Periodo Académico',
);

const submit = () => {
    if (props.mode === 'create') {
        form.post(route('admin.academic-periods.store'), {
            onSuccess: closeModal,
        });
    } else {
        form.put(
            route('admin.academic-periods.update', props.academicPeriod!.id),
            {
                onSuccess: closeModal,
            },
        );
    }
};

const closeModal = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};
</script>

<template>
    <Transition name="fade">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-900"
            >
                <!-- HEADER -->
                <div class="mb-4 flex items-center justify-between">
                    <h2
                        class="text-lg font-semibold text-gray-800 dark:text-gray-100"
                    >
                        {{ modalTitle }}
                    </h2>

                    <button
                        @click="closeModal"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        ✕
                    </button>
                </div>

                <!-- FORM -->
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- CÓDIGO -->
                    <div>
                        <label class="block text-sm font-medium">Código</label>
                        <input
                            v-model="form.code"
                            type="text"
                            :readonly="props.mode === 'edit'"
                            class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :class="
                                props.mode === 'edit'
                                    ? 'cursor-not-allowed bg-gray-100'
                                    : ''
                            "
                        />
                        <p
                            v-if="form.errors.code"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.code }}
                        </p>
                    </div>

                    <!-- NOMBRE -->
                    <div>
                        <label class="block text-sm font-medium">Nombre</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- FECHAS -->
                    <div>
                        <label class="block text-sm font-medium"
                            >Fecha inicio</label
                        >
                        <input
                            v-model="form.start_date"
                            type="date"
                            :class="[
                                'mt-1 w-full rounded-md border px-3 py-2 text-sm focus:ring-indigo-500',
                                form.errors.start_date
                                    ? 'border-red-500 focus:border-red-500'
                                    : 'focus:border-indigo-500',
                            ]"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium"
                            >Fecha fin</label
                        >
                        <input
                            v-model="form.end_date"
                            type="date"
                            :class="[
                                'mt-1 w-full rounded-md border px-3 py-2 text-sm focus:ring-indigo-500',
                                form.errors.end_date
                                    ? 'border-red-500 focus:border-red-500'
                                    : 'focus:border-indigo-500',
                            ]"
                        />
                    </div>

                    <!-- ESTADO -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            :checked="form.status === 'active'"
                            @change="
                                form.status = $event.target.checked
                                    ? 'active'
                                    : 'closed'
                            "
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-sm text-gray-700"
                            >Periodo activo</span
                        >
                    </div>

                    <!-- ACTIONS -->
                    <div class="flex justify-end gap-2 pt-4">
                        <button
                            type="button"
                            @click="closeModal"
                            class="rounded-md border px-4 py-2 text-sm hover:bg-gray-100"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{
                                props.mode === 'create'
                                    ? 'Guardar'
                                    : 'Actualizar'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
