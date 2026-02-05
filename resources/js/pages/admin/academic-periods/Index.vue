<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Pencil, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import { useSwal } from '@/composables/useSwal'
import AcademicPeriodModal from '@/components/academic-periods/AcademicPeriodModal.vue'

const Swal = useSwal()

interface AcademicPeriod {
    id: number
    code: string
    name: string
    start_date: string
    end_date: string
    status: 'active' | 'closed'
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

defineProps<{
    academicPeriods: {
        data: AcademicPeriod[]
        links: PaginationLink[]
    }
}>()

/* 🔹 Modal */
const showModal = ref(false)
const selectedAcademicPeriod = ref<AcademicPeriod | null>(null)

/* 🔹 UI acciones */
const activeAction = ref<{
    id: number | null
    type: 'edit' | 'delete' | null
}>({
    id: null,
    type: null,
})

const setActive = (id: number, type: 'edit' | 'delete') => {
    activeAction.value = { id, type }
}

/* ➕ Crear */
const openCreateModal = () => {
    selectedAcademicPeriod.value = null
    showModal.value = true
}

/* ✏️ Editar */
const openEditModal = (item: AcademicPeriod) => {
    selectedAcademicPeriod.value = item
    showModal.value = true
}

/* 🗑️ Eliminar */
const deleteAcademicPeriod = (item: AcademicPeriod) => {
    Swal.fire({
        title: '¿Eliminar periodo académico?',
        text: `El periodo "${item.code}" será eliminado`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusCancel: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.academic-periods.destroy', item.id))
        }
    })
}
</script>

<template>
    <Head title="Periodos Académicos" />

    <AppLayout>
        <div class="space-y-6 px-8 py-6">
            <!-- HEADER -->
            <div class="flex items-center justify-between border-b border-gray-200 pb-4">
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
                    Periodos Académicos
                </h1>

                <button
                    @click="openCreateModal"
                    class="inline-flex cursor-pointer items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 active:scale-95"
                >
                    + Nuevo Periodo
                </button>
            </div>

            <!-- TABLA -->
            <div
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-2 text-xs font-semibold uppercase">#</th>
                            <th class="px-6 py-2 text-xs font-semibold uppercase">Código</th>
                            <th class="px-6 py-2 text-xs font-semibold uppercase">Nombre</th>
                            <th class="px-6 py-2 text-xs font-semibold uppercase">Inicio</th>
                            <th class="px-6 py-2 text-xs font-semibold uppercase">Fin</th>
                            <th class="px-6 py-2 text-xs font-semibold uppercase">Estado</th>
                            <th class="px-6 py-2 text-right text-xs font-semibold uppercase">
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="(item, index) in academicPeriods.data"
                            :key="item.id"
                            class="group transition-all duration-200 even:bg-gray-50/40 hover:bg-indigo-50/40 dark:even:bg-gray-800/40"
                        >
                            <td class="px-6 py-2 text-sm">{{ index + 1 }}</td>
                            <td class="px-6 py-2 text-sm font-medium">{{ item.code }}</td>
                            <td class="px-6 py-2 text-sm">{{ item.name }}</td>
                            <td class="px-6 py-2 text-sm">{{ item.start_date }}</td>
                            <td class="px-6 py-2 text-sm">{{ item.end_date }}</td>
                            <td class="px-6 py-2 text-sm">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-medium"
                                    :class="{
                                        'bg-green-100 text-green-700': item.status === 'active',
                                        'bg-gray-200 text-gray-700': item.status === 'closed',
                                    }"
                                >
                                    {{ item.status === 'active' ? 'Activo' : 'Cerrado' }}
                                </span>
                            </td>

                            <!-- ACCIONES -->
                            <td class="px-6 py-2 text-right">
                                <div class="flex justify-end gap-2">
                                    <!-- EDITAR -->
                                    <button
                                        @click="
                                            setActive(item.id, 'edit');
                                            openEditModal(item);
                                        "
                                        class="relative flex h-9 w-9 items-center justify-center rounded-full transition-all duration-200"
                                        :class="[
                                            activeAction.id === item.id &&
                                            activeAction.type === 'edit'
                                                ? 'bg-indigo-600 text-white shadow-lg'
                                                : 'text-indigo-600 hover:bg-indigo-600 hover:text-white',
                                        ]"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>

                                    <!-- ELIMINAR -->
                                    <button
                                        @click="
                                            setActive(item.id, 'delete');
                                            deleteAcademicPeriod(item);
                                        "
                                        class="relative flex h-9 w-9 items-center justify-center rounded-full transition-all duration-200"
                                        :class="[
                                            activeAction.id === item.id &&
                                            activeAction.type === 'delete'
                                                ? 'bg-red-600 text-white shadow-lg'
                                                : 'text-red-600 hover:bg-red-600 hover:text-white',
                                        ]"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="academicPeriods.data.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                No hay periodos académicos registrados
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div class="flex justify-end">
                <nav class="inline-flex gap-1">
                    <Link
                        v-for="link in academicPeriods.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        class="rounded-md border px-3 py-1 text-sm transition"
                        :class="{
                            'border-indigo-600 bg-indigo-600 text-white': link.active,
                            'border-gray-300 hover:bg-gray-100': !link.active && link.url,
                            'cursor-not-allowed border-gray-200 text-gray-400': !link.url,
                        }"
                    />
                </nav>
            </div>
        </div>

        <!-- MODAL -->
        <AcademicPeriodModal
            :show="showModal"
            :academic-period="selectedAcademicPeriod"
            @close="showModal = false"
        />
    </AppLayout>
</template>
