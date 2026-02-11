<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { watch, computed } from 'vue'

interface Course {
    id: number
    name: string
    code: string
    credits: number
}

const props = defineProps<{
    show: boolean
    course: Course | null
}>()

const emit = defineEmits(['close', 'success'])

const form = useForm({
    name: '',
    code: '',
    credits: 1,
})

watch(
    () => props.show,
    (value) => {
        if (value) {
            if (props.course) {
                form.name = props.course.name
                form.code = props.course.code
                form.credits = props.course.credits
            } else {
                form.reset()
            }
        }
    }
)

const isEdit = computed(() => !!props.course)

const submit = () => {
    if (isEdit.value && props.course) {
        form.put(route('admin.courses.update', props.course.id), {
            onSuccess: () => {
                emit('success')
                form.reset()
            },
        })
    } else {
        form.post(route('admin.courses.store'), {
            onSuccess: () => {
                emit('success')
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
                    {{ isEdit ? 'Editar Curso' : 'Nuevo Curso' }}
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

                <!-- Créditos -->
                <div>
                    <label class="block text-sm font-medium">Créditos</label>
                    <input
                        v-model="form.credits"
                        type="number"
                        min="1"
                        max="20"
                        class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="form.errors.credits" class="mt-1 text-xs text-red-600">
                        {{ form.errors.credits }}
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
