<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Pencil, Trash2 } from 'lucide-vue-next'
import NotificationTemplateModal from '@/components/notifications/NotificationTemplateModal.vue'

interface Template {
    id: number
    name: string
    subject: string
    body: string
    is_active: boolean
    created_at: string
}

defineProps<{
    templates: any
}>()

/**
 * Control modal
 */
const showModal = ref(false)
const selectedTemplate = ref<Template | null>(null)

/**
 * Estado visual de botones activos
 */
const activeAction = ref<{
    id: number | null
    type: 'edit' | 'delete' | null
}>({
    id: null,
    type: null
})

const setActive = (id: number, type: 'edit' | 'delete') => {
    activeAction.value = { id, type }
}

const resetActive = () => {
    setTimeout(() => {
        activeAction.value = { id: null, type: null }
    }, 400)
}

/**
 * Crear
 */
const openCreate = () => {
    selectedTemplate.value = null
    showModal.value = true
}

/**
 * Editar
 */
const openEdit = (template: Template) => {
    setActive(template.id, 'edit')
    selectedTemplate.value = template
    showModal.value = true
}

/**
 * Eliminar
 */
const deleteTemplate = (template: Template) => {
    setActive(template.id, 'delete')

    if (!confirm(`¿Eliminar la plantilla "${template.name}"?`)) {
        resetActive()
        return
    }

    router.delete(
        route('admin.notification-templates.destroy', template.id),
        {
            preserveScroll: true,
            onFinish: () => {
                resetActive()
            }
        }
    )
}

/**
 * Paginación
 */
const goToPage = (url: string | null) => {
    if (!url) return
    router.visit(url, {
        preserveScroll: true,
        preserveState: true
    })
}
</script>

<template>
    <AppLayout>
        <Head title="Plantillas de Notificación" />

        <div class="space-y-6 p-6">

            <!-- HEADER -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">
                        Plantillas de Notificación
                    </h1>
                    <p class="text-sm text-gray-500">
                        Aquí se gestionan las plantillas que serán utilizadas
                        para generar los mensajes de los lotes académicos.
                    </p>
                </div>

                <button
                    @click="openCreate"
                    class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition"
                >
                    Nueva Plantilla
                </button>
            </div>

            <!-- TABLA -->
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3 text-left">Estado</th>
                            <th class="p-3 text-left">Nombre</th>
                            <th class="p-3 text-left">Asunto</th>
                            <th class="p-3 text-left">Creado</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="template in templates.data"
                            :key="template.id"
                            class="border-t transition"
                        >
                            <!-- Estado -->
                            <td class="p-3">
                                <span
                                    v-if="template.is_active"
                                    class="px-2 py-1 text-xs rounded bg-green-200 text-green-800 font-semibold"
                                >
                                    ACTIVA
                                </span>
                                <span
                                    v-else
                                    class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-700"
                                >
                                    Inactiva
                                </span>
                            </td>

                            <!-- Nombre -->
                            <td class="p-3 font-medium">
                                {{ template.name }}
                            </td>

                            <!-- Asunto -->
                            <td class="p-3">
                                {{ template.subject }}
                            </td>

                            <!-- Fecha -->
                            <td class="p-3 text-gray-500">
                                {{ template.created_at }}
                            </td>

                            <!-- Acciones -->
                            <td class="p-3">
                                <div class="flex justify-center gap-3">

                                    <!-- EDITAR -->
                                    <button
                                        @click="openEdit(template)"
                                        class="relative flex h-9 w-9 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="[
                                            activeAction.id === template.id &&
                                            activeAction.type === 'edit'
                                                ? 'bg-indigo-600 text-white shadow-lg'
                                                : 'text-indigo-600 hover:bg-indigo-600 hover:text-white',
                                        ]"
                                    >
                                        <Pencil
                                            class="h-4 w-4 transition-transform duration-200"
                                            :class="{
                                                'scale-110 rotate-12':
                                                    activeAction.id === template.id &&
                                                    activeAction.type === 'edit',
                                            }"
                                        />
                                    </button>

                                    <!-- ELIMINAR -->
                                    <button
                                        @click="deleteTemplate(template)"
                                        class="relative flex h-9 w-9 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                        :class="[
                                            activeAction.id === template.id &&
                                            activeAction.type === 'delete'
                                                ? 'bg-red-600 text-white shadow-lg'
                                                : 'text-red-600 hover:bg-red-600 hover:text-white',
                                        ]"
                                    >
                                        <Trash2
                                            class="h-4 w-4 transition-transform duration-200"
                                            :class="{
                                                'scale-110 rotate-12':
                                                    activeAction.id === template.id &&
                                                    activeAction.type === 'delete',
                                            }"
                                        />
                                    </button>

                                </div>
                            </td>
                        </tr>

                        <!-- Sin datos -->
                        <tr v-if="templates.data.length === 0">
                            <td colspan="5" class="p-6 text-center text-gray-500">
                                No existen plantillas registradas aún
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div
                v-if="templates.links.length > 3"
                class="flex justify-end mt-4"
            >
                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="link in templates.links"
                        :key="link.label"
                        v-html="link.label"
                        @click="goToPage(link.url)"
                        :disabled="!link.url"
                        class="px-3 py-1 text-sm rounded border transition"
                        :class="[
                            link.active
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100',
                            !link.url && 'opacity-50 cursor-not-allowed'
                        ]"
                    />
                </div>
            </div>

        </div>

        <!-- MODAL -->
        <NotificationTemplateModal
            :show="showModal"
            :template="selectedTemplate"
            @close="
                showModal = false;
                resetActive();
            "
        />
    </AppLayout>
</template>