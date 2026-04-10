<script setup lang="ts">
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { router } from '@inertiajs/vue3';
import { Building2, Mail, Search, X } from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';

interface Office {
    id: number;
    name: string;
    code: string;
    email: string;
    cc_email?: string | null;
    level: number;
    signature?: string | null;
}

const props = defineProps<{
    show: boolean;
    batchId: number | null;
    offices: Office[];
    currentOfficeId: number | null;
}>();

const emit = defineEmits(['close']);

const selectedOffice = ref<number | null>(null);
const search = ref('');

const filteredOffices = computed(() => {
    if (!search.value.trim()) return props.offices;
    const q = search.value.toLowerCase();
    return props.offices.filter(
        (o) =>
            o.name.toLowerCase().includes(q) ||
            o.code.toLowerCase().includes(q) ||
            o.email.toLowerCase().includes(q),
    );
});

const selectedOfficeData = computed(
    () => props.offices.find((o) => o.id === selectedOffice.value) ?? null,
);

const levelClasses = (level: number) => {
    if (level === 1) return 'bg-[#087ab1]/10 text-[#087ab1] ring-1 ring-[#087ab1]/20';
    if (level === 2) return 'bg-[#68c8fb]/15 text-[#087ab1] ring-1 ring-[#68c8fb]/30';
    return 'bg-gray-100 text-gray-600 ring-1 ring-gray-200';
};

let refreshInterval: any = null;

const startRefreshingOffices = () => {
    if (refreshInterval) return;
    refreshInterval = setInterval(() => {
        router.reload({
            only: ['offices'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 3000);
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
            selectedOffice.value = props.currentOfficeId ?? null;
            search.value = '';
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
    <TransitionRoot appear :show="show" as="template">
        <Dialog @close="emit('close')" class="relative z-50">
            <!-- Backdrop -->
            <TransitionChild
                as="template"
                enter="duration-500 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-300 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/40 backdrop-blur-[1px]" />
            </TransitionChild>

            <div class="fixed inset-0 flex items-center justify-center p-6">
                <TransitionChild
                    enter="duration-500 ease-out"
                    enter-from="opacity-0 scale-95 translate-y-6"
                    enter-to="opacity-100 scale-100 translate-y-0"
                    leave="duration-300 ease-in"
                    leave-from="opacity-100 scale-100 translate-y-0"
                    leave-to="opacity-0 scale-95 translate-y-4"
                    class="relative w-full max-w-2xl"
                >
                    <DialogPanel
                        class="w-full overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                    >
                        <!-- HEADER -->
                        <div
                            class="relative overflow-hidden bg-linear-to-br from-[#087ab1] to-[#68c8fb] px-6 py-6"
                        >
                            <!-- Círculos decorativos -->
                            <div
                                class="pointer-events-none absolute -top-12 -right-12 h-52 w-52 rounded-full bg-white/8"
                            />
                            <div
                                class="pointer-events-none absolute right-40 -bottom-8 h-36 w-36 rounded-full bg-white/5"
                            />
                            <!-- Líneas diagonales -->
                            <div
                                class="pointer-events-none absolute -top-2 right-28 h-[160%] w-px rotate-22 rounded-full bg-white/20"
                            />
                            <div
                                class="pointer-events-none absolute -top-2 right-20 h-[160%] w-px rotate-22 rounded-full bg-white/14"
                            />
                            <div
                                class="pointer-events-none absolute -top-2 right-12 h-[160%] w-px rotate-22 rounded-full bg-white/8"
                            />

                            <div class="relative flex items-stretch gap-4">
                                <!-- Título + badge selección -->
                                <div
                                    class="flex flex-1 flex-col justify-between gap-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25"
                                        >
                                            <Building2
                                                class="h-5 w-5 text-white"
                                            />
                                        </div>
                                        <div>
                                            <DialogTitle
                                                class="text-base font-bold text-white"
                                            >
                                                Seleccionar Oficina
                                            </DialogTitle>
                                            <p class="text-xs text-[#68c8fb]">
                                                Elige la oficina remitente de
                                                las notificaciones
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Badge oficina seleccionada -->
                                    <span
                                        :class="[
                                            'inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1 transition-all duration-300',
                                            selectedOffice
                                                ? 'bg-emerald-500/20 text-emerald-100 ring-emerald-400/30'
                                                : 'bg-white/10 text-white/60 ring-white/20',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 rounded-full transition-colors duration-300',
                                                selectedOffice
                                                    ? 'animate-pulse bg-emerald-400'
                                                    : 'bg-white/40',
                                            ]"
                                        />
                                        <span class="max-w-[180px] truncate">
                                            {{
                                                selectedOfficeData?.name ??
                                                'Ninguna seleccionada'
                                            }}
                                        </span>
                                    </span>
                                </div>

                                <!-- Contador -->
                                <div
                                    class="flex shrink-0 flex-col items-center justify-center gap-1 rounded-xl bg-white/10 px-5 ring-1 ring-white/20"
                                >
                                    <span class="text-2xl font-bold text-white">
                                        {{ offices.length }}
                                    </span>
                                    <span
                                        class="text-[10px] font-medium tracking-wide text-white/60 uppercase"
                                    >
                                        oficinas
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- BÚSQUEDA -->
                        <div class="border-b border-gray-100 px-5 py-3">
                            <div class="relative">
                                <Search
                                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                                />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Buscar por nombre, código o correo..."
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2 pr-9 pl-9 text-sm text-gray-700 transition focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none"
                                />
                                <button
                                    v-if="search"
                                    @click="search = ''"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- CUERPO: lista + preview -->
                        <div class="flex" style="max-height: 380px">
                            <!-- LISTA -->
                            <div
                                class="w-1/2 overflow-y-auto border-r border-gray-100 px-4 py-4"
                            >
                                <!-- Sin resultados -->
                                <div
                                    v-if="
                                        filteredOffices.length === 0 &&
                                        offices.length > 0
                                    "
                                    class="flex flex-col items-center gap-3 py-8 text-center"
                                >
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100"
                                    >
                                        <Search
                                            class="h-4 w-4 text-gray-400"
                                        />
                                    </div>
                                    <p
                                        class="text-xs font-medium text-gray-500"
                                    >
                                        Sin resultados para "{{ search }}"
                                    </p>
                                    <button
                                        @click="search = ''"
                                        class="text-xs font-medium text-[#087ab1] hover:underline"
                                    >
                                        Limpiar búsqueda
                                    </button>
                                </div>

                                <!-- Sin oficinas -->
                                <div
                                    v-else-if="offices.length === 0"
                                    class="flex flex-col items-center gap-3 py-8 text-center"
                                >
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-[#087ab1]/10"
                                    >
                                        <Building2
                                            class="h-4 w-4 text-[#087ab1]/60"
                                        />
                                    </div>
                                    <p
                                        class="text-xs font-medium text-gray-500"
                                    >
                                        No hay oficinas disponibles
                                    </p>
                                </div>

                                <!-- Cards -->
                                <div v-else class="space-y-2">
                                    <label
                                        v-for="office in filteredOffices"
                                        :key="office.id"
                                        class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition-all duration-150"
                                        :class="
                                            selectedOffice === office.id
                                                ? 'border-[#087ab1] bg-[#087ab1]/5 shadow-sm shadow-[#087ab1]/10'
                                                : 'border-gray-200 hover:border-[#68c8fb]/50 hover:bg-[#68c8fb]/5'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            :value="office.id"
                                            v-model="selectedOffice"
                                            class="sr-only"
                                        />

                                        <!-- Ícono -->
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors"
                                            :class="
                                                selectedOffice === office.id
                                                    ? 'bg-[#087ab1] text-white'
                                                    : 'bg-[#087ab1]/10 text-[#087ab1]'
                                            "
                                        >
                                            <Building2 class="h-3.5 w-3.5" />
                                        </div>

                                        <!-- Info -->
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate text-sm font-medium"
                                                :class="
                                                    selectedOffice === office.id
                                                        ? 'text-[#087ab1]'
                                                        : 'text-gray-800'
                                                "
                                            >
                                                {{ office.name }}
                                            </p>
                                            <p
                                                class="truncate text-[10px] text-gray-400"
                                            >
                                                {{ office.code }}
                                            </p>
                                        </div>

                                        <!-- Indicadores -->
                                        <div
                                            class="flex shrink-0 items-center gap-1.5"
                                        >
                                            <span
                                                v-if="
                                                    currentOfficeId === office.id
                                                "
                                                class="rounded-full bg-[#087ab1]/10 px-1.5 py-0.5 text-[9px] font-semibold text-[#087ab1]"
                                            >
                                                Actual
                                            </span>
                                            <div
                                                class="h-4 w-4 rounded-full border-2 transition-all"
                                                :class="
                                                    selectedOffice === office.id
                                                        ? 'border-[#087ab1] bg-[#087ab1]'
                                                        : 'border-gray-200'
                                                "
                                            >
                                                <svg
                                                    v-if="
                                                        selectedOffice ===
                                                        office.id
                                                    "
                                                    class="h-full w-full text-white"
                                                    viewBox="0 0 16 16"
                                                    fill="none"
                                                >
                                                    <path
                                                        d="M4 8l3 3 5-5"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                </svg>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- PANEL PREVIEW -->
                            <div class="flex w-1/2 flex-col bg-gray-50">
                                <!-- Sin selección -->
                                <div
                                    v-if="!selectedOfficeData"
                                    class="flex flex-1 flex-col items-center justify-center gap-3 px-6 text-center"
                                >
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-full bg-[#087ab1]/8"
                                    >
                                        <Building2
                                            class="h-5 w-5 text-[#087ab1]/40"
                                        />
                                    </div>
                                    <p class="text-xs text-gray-400">
                                        Selecciona una oficina para ver su
                                        información
                                    </p>
                                </div>

                                <!-- Preview -->
                                <div
                                    v-else
                                    class="flex flex-1 flex-col overflow-hidden"
                                >
                                    <!-- Cabecera preview -->
                                    <div
                                        class="flex items-center gap-1.5 border-b border-gray-200 bg-white px-4 py-2.5"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-[#087ab1]"
                                        />
                                        <span
                                            class="text-[10px] font-semibold tracking-wide text-gray-400 uppercase"
                                        >
                                            Detalle de la oficina
                                        </span>
                                        <!-- Badge nivel -->
                                        <span
                                            class="ml-auto inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="
                                                levelClasses(
                                                    selectedOfficeData.level,
                                                )
                                            "
                                        >
                                            Nivel
                                            {{ selectedOfficeData.level }}
                                        </span>
                                    </div>

                                    <!-- Contenido -->
                                    <div
                                        class="flex flex-1 flex-col gap-4 overflow-y-auto p-4"
                                    >
                                        <!-- Firma -->
                                        <div class="space-y-1.5">
                                            <p
                                                class="text-[10px] font-semibold tracking-wide text-gray-400 uppercase"
                                            >
                                                Firma del remitente
                                            </p>
                                            <div
                                                class="flex items-center justify-center rounded-xl border border-gray-200 bg-white p-3 shadow-sm"
                                                style="min-height: 80px"
                                            >
                                                <img
                                                    v-if="
                                                        selectedOfficeData.signature
                                                    "
                                                    :src="`/storage/${selectedOfficeData.signature}`"
                                                    :alt="`Firma de ${selectedOfficeData.name}`"
                                                    class="max-h-16 max-w-full object-contain"
                                                />
                                                <div
                                                    v-else
                                                    class="flex flex-col items-center gap-1.5 text-center"
                                                >
                                                    <Building2
                                                        class="h-6 w-6 text-gray-300"
                                                    />
                                                    <p
                                                        class="text-[10px] text-gray-400 italic"
                                                    >
                                                        Sin firma registrada
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Datos de contacto -->
                                        <div class="space-y-1.5">
                                            <p
                                                class="text-[10px] font-semibold tracking-wide text-gray-400 uppercase"
                                            >
                                                Datos de envío
                                            </p>
                                            <div
                                                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                                            >
                                                <!-- Código -->
                                                <div
                                                    class="flex items-center gap-3 border-b border-gray-100 px-3 py-2.5"
                                                >
                                                    <div
                                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-[#087ab1]/10"
                                                    >
                                                        <Building2
                                                            class="h-3 w-3 text-[#087ab1]"
                                                        />
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p
                                                            class="text-[10px] text-gray-400"
                                                        >
                                                            Código
                                                        </p>
                                                        <p
                                                            class="truncate text-xs font-semibold text-gray-700"
                                                        >
                                                            {{
                                                                selectedOfficeData.code
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Email principal -->
                                                <div
                                                    class="flex items-center gap-3 px-3 py-2.5"
                                                    :class="
                                                        selectedOfficeData.cc_email
                                                            ? 'border-b border-gray-100'
                                                            : ''
                                                    "
                                                >
                                                    <div
                                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-[#087ab1]/10"
                                                    >
                                                        <Mail
                                                            class="h-3 w-3 text-[#087ab1]"
                                                        />
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p
                                                            class="text-[10px] text-gray-400"
                                                        >
                                                            Correo remitente
                                                        </p>
                                                        <p
                                                            class="truncate text-xs font-semibold text-gray-700"
                                                        >
                                                            {{
                                                                selectedOfficeData.email
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- CC Email -->
                                                <div
                                                    v-if="
                                                        selectedOfficeData.cc_email
                                                    "
                                                    class="flex items-center gap-3 px-3 py-2.5"
                                                >
                                                    <div
                                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-[#68c8fb]/20"
                                                    >
                                                        <Mail
                                                            class="h-3 w-3 text-[#087ab1]/70"
                                                        />
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p
                                                            class="text-[10px] text-gray-400"
                                                        >
                                                            Copia (CC)
                                                        </p>
                                                        <p
                                                            class="truncate text-xs font-semibold text-gray-700"
                                                        >
                                                            {{
                                                                selectedOfficeData.cc_email
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div
                            class="flex items-center justify-between border-t border-gray-100 bg-gray-50/70 px-6 py-4"
                        >
                            <p class="text-xs text-gray-400">
                                <template
                                    v-if="search && filteredOffices.length > 0"
                                >
                                    {{ filteredOffices.length }} de
                                    {{ offices.length }} oficinas
                                </template>
                                <template v-else-if="!search">
                                    {{ offices.length }} oficina{{
                                        offices.length !== 1 ? 's' : ''
                                    }}
                                    disponible{{
                                        offices.length !== 1 ? 's' : ''
                                    }}
                                </template>
                            </p>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                                    @click="emit('close')"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    class="flex items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#087ab1]/30 transition-all hover:opacity-90 hover:shadow-lg hover:shadow-[#087ab1]/40 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="!selectedOffice"
                                    @click="save"
                                >
                                    <Building2 class="h-4 w-4" />
                                    Guardar Oficina
                                </button>
                            </div>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
