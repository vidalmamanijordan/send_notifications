<script setup lang="ts">
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Template {
    id?: number;
    name: string;
    subject: string;
    body: string;
    is_active: boolean | number | string;
}

const props = defineProps<{
    show: boolean;
    template?: Template | null;
}>();

const emit = defineEmits(['close']);

const isEdit = computed(() => !!props.template);

const form = useForm<Template>({
    name: '',
    subject: '',
    body: '',
    is_active: true,
});

/**
 * Cuando se abre en modo edición,
 * cargamos los datos en el formulario
 */
watch(
    () => props.template,
    (value) => {
        if (value) {
            form.name = value.name;
            form.subject = value.subject;
            form.body = value.body;

            // 🔥 SOLUCIÓN REAL AQUÍ
            form.is_active = Boolean(
                value.is_active === true ||
                value.is_active === 1 ||
                value.is_active === '1',
            );
        } else {
            form.reset();
            form.is_active = true; // default al crear
        }
    },
    { immediate: true },
);

const submit = () => {
    if (isEdit.value && props.template?.id) {
        form.put(
            route('admin.notification-templates.update', props.template.id),
            {
                onSuccess: () => {
                    emit('close');
                    form.reset();
                },
            },
        );
    } else {
        form.post(route('admin.notification-templates.store'), {
            onSuccess: () => {
                emit('close');
                form.reset();
            },
        });
    }
};
</script>

<template>
    <Dialog :open="show" @close="emit('close')" class="relative z-50">
        <div class="fixed inset-0 bg-black/30" />

        <div class="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel class="w-full max-w-2xl rounded-xl bg-white shadow-xl">
                <!-- HEADER -->
                <div class="flex items-center justify-between border-b p-4">
                    <DialogTitle class="text-lg font-semibold">
                        {{ isEdit ? 'Editar Plantilla' : 'Nueva Plantilla' }}
                    </DialogTitle>

                    <button
                        @click="emit('close')"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- BODY -->
                <div class="space-y-4 p-4">
                    <!-- Nombre -->
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Nombre
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border px-3 py-2"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Asunto -->
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Asunto
                        </label>
                        <input
                            v-model="form.subject"
                            type="text"
                            class="w-full rounded-lg border px-3 py-2"
                        />
                        <p
                            v-if="form.errors.subject"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.subject }}
                        </p>
                    </div>

                    <!-- Cuerpo -->
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Cuerpo del mensaje
                        </label>
                        <textarea
                            v-model="form.body"
                            rows="6"
                            class="w-full rounded-lg border px-3 py-2"
                        />
                        <p
                            v-if="form.errors.body"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.body }}
                        </p>
                    </div>

                    <!-- Activo -->
                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            v-model="form.is_active"
                            class="rounded"
                        />
                        <label class="text-sm"> Plantilla activa </label>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="flex justify-end gap-2 border-t bg-gray-50 p-4">
                    <button
                        class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300"
                        @click="emit('close')"
                    >
                        Cancelar
                    </button>

                    <button
                        class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        {{
                            form.processing
                                ? 'Guardando...'
                                : isEdit
                                  ? 'Actualizar'
                                  : 'Guardar'
                        }}
                    </button>
                </div>
            </DialogPanel>
        </div>
    </Dialog>
</template>
