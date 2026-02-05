<script setup lang="ts">
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'

/* 🔹 Props */
interface Props {
    show: boolean
    mode: 'create' | 'edit'
    academicPeriod?: {
        id: number
        name: string
        start_date: string
        end_date: string
        is_active: boolean
    } | null
}

const props = defineProps<Props>()
const emit = defineEmits(['close'])

/* 🔹 Formulario */
const form = useForm({
    name: '',
    start_date: '',
    end_date: '',
    is_active: true,
})

/* 🔹 Cargar datos cuando es edición */
watch(
    () => props.show,
    (value) => {
        if (value) {
            if (props.mode === 'edit' && props.academicPeriod) {
                form.name = props.academicPeriod.name
                form.start_date = props.academicPeriod.start_date
                form.end_date = props.academicPeriod.end_date
                form.is_active = props.academicPeriod.is_active
            } else {
                form.reset()
                form.is_active = true
            }
        }
    }
)

/* 🔹 Título dinámico */
const modalTitle = computed(() =>
    props.mode === 'create'
        ? 'Nuevo Periodo Académico'
        : 'Editar Periodo Académico'
)

/* 🔹 Submit */
const submit = () => {
    if (props.mode === 'create') {
        form.post(route('admin.academic-periods.store'), {
            onSuccess: () => closeModal(),
        })
    } else {
        form.put(
            route('admin.academic-periods.update', props.academicPeriod?.id),
            {
                onSuccess: () => closeModal(),
            }
        )
    }
}

/* 🔹 Cerrar */
const closeModal = () => {
    form.reset()
    emit('close')
}
</script>

<template>
    <Transition name="fade">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-900">
                <!-- HEADER -->
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
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
                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-medium">Nombre</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="mt-1 w-full rounded-md border px-3 py-2 text-sm
                                   focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Fecha inicio -->
                    <div>
                        <label class="block text-sm font-medium">
                            Fecha inicio
                        </label>
                        <input
                            v-model="form.start_date"
                            type="date"
                            class="mt-1 w-full rounded-md border px-3 py-2 text-sm
                                   focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">
                            {{ form.errors.start_date }}
                        </p>
                    </div>

                    <!-- Fecha fin -->
                    <div>
                        <label class="block text-sm font-medium">
                            Fecha fin
                        </label>
                        <input
                            v-model="form.end_date"
                            type="date"
                            class="mt-1 w-full rounded-md border px-3 py-2 text-sm
                                   focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">
                            {{ form.errors.end_date }}
                        </p>
                    </div>

                    <!-- Activo -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-sm text-gray-700">
                            Periodo activo
                        </span>
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
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm
                                   font-medium text-white hover:bg-indigo-700
                                   disabled:opacity-50"
                        >
                            {{ props.mode === 'create' ? 'Guardar' : 'Actualizar' }}
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
