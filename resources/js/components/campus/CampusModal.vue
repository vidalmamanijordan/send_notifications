<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { watch, computed } from 'vue'

interface Campus {
    id: number
    name: string
    code?: string
}

const props = defineProps<{
    show: boolean
    campus: Campus | null
}>()

const emit = defineEmits(['close'])

// Formulario Inertia
const form = useForm({
    name: '',
    code: '',
})

// 🔁 Detectar cuando se abre el modal
watch(
    () => props.show,
    (value) => {
        if (value) {
            if (props.campus) {
                // EDIT
                form.name = props.campus.name
                form.code = props.campus.code ?? ''
            } else {
                // CREATE
                form.reset()
            }
        }
    }
)

// 🧠 Saber si es edición
const isEdit = computed(() => !!props.campus)

// 💾 Enviar formulario
const submit = () => {
    if (isEdit.value && props.campus) {
        // UPDATE
        form.put(route('admin.campus.update', props.campus.id), {
            onSuccess: () => {
                emit('close')
                form.reset()
            },
        })
    } else {
        // STORE
        form.post(route('admin.campus.store'), {
            onSuccess: () => {
                emit('close')
                form.reset()
            },
        })
    }
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    >
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-900">
            <!-- HEADER -->
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {{ isEdit ? 'Editar Campus' : 'Nuevo Campus' }}
                </h2>

                <button
                    @click="emit('close')"
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

                <!-- ACTIONS -->
                <div class="flex justify-end gap-2 pt-4">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="rounded-md border px-4 py-2 text-sm hover:bg-gray-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ isEdit ? 'Actualizar' : 'Guardar' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
