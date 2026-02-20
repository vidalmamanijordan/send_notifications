<script setup lang="ts">
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
import { X } from 'lucide-vue-next';

interface Teacher {
    full_name: string;
}

interface Detail {
    id: number;
    pending_courses_count: number;
    status_label: string;
    teacher?: Teacher;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface BatchResponse {
    id: number;
    name: string;
    status_label: string;
    teachers_count: number;
    total_pending_courses: number;
    academic_period?: { name: string };
    campus?: { name: string };
    details: {
        data: Detail[];
        links: PaginationLink[];
    };
}

const props = defineProps<{
    show: boolean;
    batch: BatchResponse | null;
}>();

const emit = defineEmits(['close', 'paginate', 'send']);

const getBatchStatusColor = (status: string) => {
    switch (status) {
        case 'Borrador':
            return 'bg-gray-100 text-gray-600';
        case 'Procesado':
            return 'bg-green-100 text-green-700';
        case 'Enviado':
            return 'bg-blue-100 text-blue-700';
        default:
            return 'bg-gray-100 text-gray-600';
    }
};

const getDetailStatusColor = (status: string) => {
    switch (status) {
        case 'Pendiente':
            return 'bg-yellow-100 text-yellow-700';
        case 'Notificado':
            return 'bg-green-100 text-green-700';
        case 'Error':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};

const goToPage = (url: string | null) => {
    if (!url) return;
    emit('paginate', url);
};
</script>

<template>
    <Dialog :open="show" @close="emit('close')" class="relative z-50">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black/30" />

        <div class="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel
                class="flex max-h-[80vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-xl"
            >
                <!-- HEADER -->
                <div class="flex items-center justify-between border-b p-4">
                    <div>
                        <DialogTitle class="text-lg font-semibold">
                            Notificación Rubros Vencidos
                        </DialogTitle>
                        <p class="text-sm text-gray-500">
                            {{ batch?.academic_period?.name ?? '-' }} —
                            {{ batch?.campus?.name ?? '-' }}
                        </p>
                    </div>

                    <button
                        @click="emit('close')"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- KPIs -->
                <div class="grid grid-cols-3 gap-4 border-b p-4">
                    <div
                        class="flex flex-col items-start rounded-lg border p-3"
                    >
                        <p class="text-xs text-gray-400">Estado</p>
                        <span
                            class="rounded px-2 py-1 text-sm font-medium"
                            :class="
                                getBatchStatusColor(batch?.status_label ?? '')
                            "
                        >
                            {{ batch?.status_label ?? '-' }}
                        </span>
                    </div>

                    <div
                        class="flex flex-col items-start rounded-lg border p-3"
                    >
                        <p class="text-xs text-gray-400">Docentes</p>
                        <p class="text-xl font-semibold">
                            {{ batch?.teachers_count ?? 0 }}
                        </p>
                    </div>

                    <div
                        class="flex flex-col items-start rounded-lg border p-3"
                    >
                        <p class="text-xs text-gray-400">Cursos vencidos</p>
                        <p class="text-xl font-semibold text-red-500">
                            {{ batch?.total_pending_courses ?? 0 }}
                        </p>
                    </div>
                </div>

                <!-- TABLA -->
                <div class="flex-1 overflow-y-auto px-4 py-2">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10 bg-gray-50">
                            <tr class="border-b text-left">
                                <th class="px-2 py-2">Docente</th>
                                <th class="px-2 py-2">Cursos vencidos</th>
                                <th class="px-2 py-2">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="d in batch?.details.data ?? []"
                                :key="d.id"
                                class="border-b hover:bg-gray-50"
                            >
                                <td class="px-2 py-2">
                                    {{
                                        d.teacher?.full_name ?? 'No disponible'
                                    }}
                                </td>
                                <td class="px-2 py-2">
                                    {{ d.pending_courses_count }}
                                </td>
                                <td class="px-2 py-2">
                                    <span
                                        class="rounded px-2 py-1 text-xs font-medium"
                                        :class="
                                            getDetailStatusColor(d.status_label)
                                        "
                                    >
                                        {{ d.status_label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINACIÓN -->
                <div
                    class="flex flex-wrap justify-center gap-1 border-t px-4 py-2"
                >
                    <button
                        v-for="(link, i) in batch?.details.links ?? []"
                        :key="i"
                        v-html="link.label"
                        @click="goToPage(link.url)"
                        class="rounded border px-3 py-1 text-sm"
                        :class="[
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-100',
                            !link.url && 'cursor-not-allowed opacity-40',
                        ]"
                    />
                </div>

                <!-- FOOTER -->
                <div class="flex justify-end gap-2 border-t bg-gray-50 p-4">
                    <button
                        class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300"
                        @click="emit('close')"
                    >
                        Cerrar
                    </button>
                    <button
                        class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                        @click="emit('send')"
                    >
                        Enviar notificaciones
                    </button>
                </div>
            </DialogPanel>
        </div>
    </Dialog>
</template>
