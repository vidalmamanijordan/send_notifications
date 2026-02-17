<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'

defineProps<{
    records: any
    activeBatchId?: number
}>()

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
        <Head title="Docentes con Rubros Vencidos" />

        <div class="space-y-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">
                        Docentes con Rubros Vencidos
                    </h1>
                    <p class="text-sm text-gray-500">
                        Se muestran todos los registros importados.
                        El lote activo será utilizado para futuras notificaciones.
                    </p>
                </div>

                <div v-if="activeBatchId"
                     class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full">
                    Último lote activo: #{{ activeBatchId }}
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3 text-left">Estado</th>
                            <th class="p-3 text-left">Lote</th>
                            <th class="p-3 text-left">Fecha Carga</th>
                            <th class="p-3 text-left">Periodo</th>
                            <th class="p-3 text-left">Campus</th>
                            <th class="p-3 text-left">Docente</th>
                            <th class="p-3 text-left">Curso</th>
                            <th class="p-3 text-left">Ciclo</th>
                            <th class="p-3 text-left">Grupo</th>
                            <th class="p-3 text-left">Rubros Vencidos</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="row in records.data"
                            :key="row.id"
                            class="border-t transition"
                            :class="{ 'bg-green-50': row.is_active_batch }"
                        >
                            <td class="p-3">
                                <span
                                    v-if="row.is_active_batch"
                                    class="px-2 py-1 text-xs rounded bg-green-200 text-green-800 font-semibold"
                                >
                                    ACTIVO
                                </span>
                                <span
                                    v-else
                                    class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-700"
                                >
                                    Histórico
                                </span>
                            </td>

                            <td class="p-3 font-medium">
                                #{{ row.import_batch_id }}
                            </td>

                            <td class="p-3">
                                {{ row.imported_at }}
                            </td>

                            <td class="p-3">
                                {{ row.academic_period }}
                            </td>

                            <td class="p-3">
                                {{ row.campus }}
                            </td>

                            <td class="p-3">
                                {{ row.teacher }}
                            </td>

                            <td class="p-3">
                                {{ row.course }}
                            </td>

                            <td class="p-3">
                                {{ row.cycle }}
                            </td>

                            <td class="p-3">
                                {{ row.group }}
                            </td>

                            <td class="p-3 text-red-600 font-semibold">
                                {{ row.expired_components }}
                            </td>
                        </tr>

                        <tr v-if="records.data.length === 0">
                            <td colspan="10" class="p-6 text-center text-gray-500">
                                No existen registros cargados aún
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <!-- Paginación -->
<div v-if="records.links.length > 3"
     class="flex justify-end mt-4">

    <div class="flex flex-wrap gap-1">

        <button
            v-for="link in records.links"
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
    </AppLayout>
</template>
