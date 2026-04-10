<script setup lang="ts">
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { CheckCircle, FileText, Mail, Search, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

interface Template {
    id: number;
    name: string;
    subject: string;
    body: string;
    is_active: boolean | number;
}

const props = defineProps<{
    show: boolean;
    batchId: number | null;
    currentTemplateId: number | null;
}>();

const emit = defineEmits(['close']);

const selectedTemplate = ref<number | null>(null);
const templates = ref<Template[]>([]);
const loading = ref(false);
const updated = ref(false);
const search = ref('');

const filteredTemplates = computed(() => {
    if (!search.value.trim()) return templates.value;
    return templates.value.filter((t) =>
        t.name.toLowerCase().includes(search.value.toLowerCase()),
    );
});

const selectedTemplateData = computed(
    () => templates.value.find((t) => t.id === selectedTemplate.value) ?? null,
);

const previewBody = computed(() => {
    const body = selectedTemplateData.value?.body ?? '';
    const first = body.split('\n')[0].trim();
    if (!first) return null;
    return first.length > 120 ? first.slice(0, 120) + '…' : first;
});

let intervalId: ReturnType<typeof setInterval> | null = null;

/* =========================
   API CALL CENTRALIZADA
========================= */
const fetchTemplates = async (): Promise<Template[]> => {
    const url = route('admin.notification-templates.list');
    const { data } = await axios.get(url);
    return data ?? [];
};

/* =========================
   CARGA INICIAL
========================= */
const loadTemplates = async () => {
    try {
        loading.value = true;
        templates.value = await fetchTemplates();
    } catch (error) {
        console.error('Error cargando plantillas', error);
    } finally {
        loading.value = false;
    }
};

/* =========================
   REFRESH SIN PERDER SELECCIÓN
========================= */
const refreshTemplates = async () => {
    try {
        const newTemplates = await fetchTemplates();

        if (
            selectedTemplate.value &&
            !newTemplates.some((t) => t.id === selectedTemplate.value)
        ) {
            selectedTemplate.value = null;
        }

        const changed =
            newTemplates.length !== templates.value.length ||
            newTemplates.some((t, i) => {
                const old = templates.value[i];
                return !old || t.id !== old.id || t.name !== old.name;
            });

        if (changed) {
            templates.value = [...newTemplates];
            updated.value = true;
            setTimeout(() => (updated.value = false), 2000);
        }
    } catch (error) {
        console.error('Error refrescando plantillas', error);
    }
};

/* =========================
   WATCH MODAL
========================= */
watch(
    () => props.show,
    async (open) => {
        if (!open) {
            stopAutoRefresh();
            search.value = '';
            return;
        }

        selectedTemplate.value = props.currentTemplateId ?? null;
        await loadTemplates();
        startAutoRefresh();
    },
);

/* =========================
   AUTO REFRESH
========================= */
const startAutoRefresh = () => {
    if (intervalId) return;
    intervalId = setInterval(refreshTemplates, 5000);
};

const stopAutoRefresh = () => {
    if (!intervalId) return;
    clearInterval(intervalId);
    intervalId = null;
};

/* =========================
   LIMPIAR AL DESTRUIR
========================= */
onBeforeUnmount(() => {
    stopAutoRefresh();
});

/* =========================
   ASOCIAR
========================= */
const attachTemplate = () => {
    if (!selectedTemplate.value || !props.batchId) return;

    router.patch(
        route('admin.notification-batches.attach-template', props.batchId),
        {
            notification_template_id: selectedTemplate.value,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                emit('close');
            },

            onError: (errors) => {
                if (errors && Object.keys(errors).length > 0) {
                    alert(Object.values(errors)[0]);
                }
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
                                            <FileText
                                                class="h-5 w-5 text-white"
                                            />
                                        </div>
                                        <div>
                                            <DialogTitle
                                                class="text-base font-bold text-white"
                                            >
                                                Enlazar Plantilla
                                            </DialogTitle>
                                            <p class="text-xs text-[#68c8fb]">
                                                Selecciona una plantilla para
                                                este lote
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Badge plantilla seleccionada -->
                                    <span
                                        :class="[
                                            'inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1 transition-all duration-300',
                                            selectedTemplate
                                                ? 'bg-emerald-500/20 text-emerald-100 ring-emerald-400/30'
                                                : 'bg-white/10 text-white/60 ring-white/20',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 rounded-full transition-colors duration-300',
                                                selectedTemplate
                                                    ? 'animate-pulse bg-emerald-400'
                                                    : 'bg-white/40',
                                            ]"
                                        />
                                        <span class="max-w-[180px] truncate">
                                            {{
                                                selectedTemplateData?.name ??
                                                'Ninguna seleccionada'
                                            }}
                                        </span>
                                    </span>
                                </div>

                                <!-- Contador + preview mini -->
                                <div
                                    class="flex shrink-0 flex-col items-center justify-center gap-1 rounded-xl bg-white/10 px-5 ring-1 ring-white/20"
                                >
                                    <span class="text-2xl font-bold text-white">
                                        {{ templates.length }}
                                    </span>
                                    <span
                                        class="text-[10px] font-medium tracking-wide text-white/60 uppercase"
                                    >
                                        plantillas
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- BARRA DE BÚSQUEDA -->
                        <div class="border-b border-gray-100 px-5 py-3">
                            <div class="relative">
                                <Search
                                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                                />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Buscar plantilla por nombre..."
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

                        <!-- CUERPO: lista + preview lado a lado -->
                        <div class="flex" style="max-height: 380px">
                            <!-- LISTA DE PLANTILLAS -->
                            <div
                                class="w-1/2 overflow-y-auto border-r border-gray-100 px-4 py-4"
                            >
                                <!-- Skeleton loading -->
                                <div v-if="loading" class="space-y-2">
                                    <div
                                        v-for="i in 4"
                                        :key="i"
                                        class="flex animate-pulse items-center gap-3 rounded-xl border border-gray-100 p-3.5"
                                    >
                                        <div
                                            class="h-9 w-9 rounded-lg bg-gray-200"
                                        />
                                        <div class="flex-1 space-y-1.5">
                                            <div
                                                class="h-3 w-3/4 rounded bg-gray-200"
                                            />
                                            <div
                                                class="h-2.5 w-1/2 rounded bg-gray-100"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Sin resultados de búsqueda -->
                                <div
                                    v-else-if="
                                        !loading &&
                                        filteredTemplates.length === 0 &&
                                        templates.length > 0
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
                                    <p class="text-xs font-medium text-gray-500">
                                        Sin resultados para "{{ search }}"
                                    </p>
                                    <button
                                        @click="search = ''"
                                        class="text-xs font-medium text-[#087ab1] hover:underline"
                                    >
                                        Limpiar búsqueda
                                    </button>
                                </div>

                                <!-- Sin plantillas -->
                                <div
                                    v-else-if="
                                        !loading && templates.length === 0
                                    "
                                    class="flex flex-col items-center gap-3 py-8 text-center"
                                >
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-[#087ab1]/10"
                                    >
                                        <FileText
                                            class="h-4 w-4 text-[#087ab1]/60"
                                        />
                                    </div>
                                    <p class="text-xs font-medium text-gray-500">
                                        No hay plantillas registradas
                                    </p>
                                </div>

                                <!-- Cards -->
                                <div v-else class="space-y-2">
                                    <!-- Toast actualizado -->
                                    <Transition
                                        enter-active-class="duration-300 ease-out"
                                        enter-from-class="opacity-0 -translate-y-2"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="duration-200 ease-in"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 -translate-y-2"
                                    >
                                        <div
                                            v-if="updated"
                                            class="mb-2 flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                                            />
                                            Lista actualizada
                                        </div>
                                    </Transition>

                                    <label
                                        v-for="template in filteredTemplates"
                                        :key="template.id"
                                        class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition-all duration-150"
                                        :class="
                                            selectedTemplate === template.id
                                                ? 'border-[#087ab1] bg-[#087ab1]/5 shadow-sm shadow-[#087ab1]/10'
                                                : 'border-gray-200 hover:border-[#68c8fb]/50 hover:bg-[#68c8fb]/5'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            :value="template.id"
                                            v-model="selectedTemplate"
                                            class="sr-only"
                                        />

                                        <!-- Ícono -->
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors"
                                            :class="
                                                selectedTemplate === template.id
                                                    ? 'bg-[#087ab1] text-white'
                                                    : 'bg-[#087ab1]/10 text-[#087ab1]'
                                            "
                                        >
                                            <FileText class="h-3.5 w-3.5" />
                                        </div>

                                        <!-- Nombre -->
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate text-sm font-medium"
                                                :class="
                                                    selectedTemplate ===
                                                    template.id
                                                        ? 'text-[#087ab1]'
                                                        : 'text-gray-800'
                                                "
                                            >
                                                {{ template.name }}
                                            </p>
                                            <p
                                                v-if="
                                                    currentTemplateId ===
                                                    template.id
                                                "
                                                class="mt-0.5 text-[10px] text-[#087ab1]/70"
                                            >
                                                Plantilla actual
                                            </p>
                                        </div>

                                        <!-- Indicadores -->
                                        <div class="flex shrink-0 items-center gap-1.5">
                                            <span
                                                v-if="
                                                    currentTemplateId ===
                                                    template.id
                                                "
                                                class="rounded-full bg-[#087ab1]/10 px-1.5 py-0.5 text-[9px] font-semibold text-[#087ab1]"
                                            >
                                                Actual
                                            </span>
                                            <CheckCircle
                                                v-if="
                                                    selectedTemplate ===
                                                    template.id
                                                "
                                                class="h-4 w-4 text-[#087ab1]"
                                            />
                                            <div
                                                v-else
                                                class="h-4 w-4 rounded-full border-2 border-gray-200"
                                            />
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- PANEL DE VISTA PREVIA -->
                            <div class="flex w-1/2 flex-col bg-gray-50">
                                <!-- Sin selección -->
                                <div
                                    v-if="!selectedTemplateData"
                                    class="flex flex-1 flex-col items-center justify-center gap-3 px-6 text-center"
                                >
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-full bg-[#087ab1]/8"
                                    >
                                        <FileText
                                            class="h-5 w-5 text-[#087ab1]/40"
                                        />
                                    </div>
                                    <p class="text-xs text-gray-400">
                                        Selecciona una plantilla para ver su
                                        vista previa
                                    </p>
                                </div>

                                <!-- Preview de la plantilla seleccionada -->
                                <div v-else class="flex flex-1 flex-col overflow-hidden">
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
                                            Vista previa
                                        </span>
                                        <!-- Badge activo/inactivo -->
                                        <span
                                            class="ml-auto inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium"
                                            :class="
                                                selectedTemplateData.is_active
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : 'bg-gray-100 text-gray-500'
                                            "
                                        >
                                            <span
                                                class="h-1 w-1 rounded-full"
                                                :class="
                                                    selectedTemplateData.is_active
                                                        ? 'bg-emerald-500'
                                                        : 'bg-gray-400'
                                                "
                                            />
                                            {{
                                                selectedTemplateData.is_active
                                                    ? 'Activa'
                                                    : 'Inactiva'
                                            }}
                                        </span>
                                    </div>

                                    <!-- Contenido preview -->
                                    <div class="flex flex-1 flex-col overflow-y-auto p-4 gap-3">
                                        <!-- Asunto -->
                                        <div
                                            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                                        >
                                            <div
                                                class="flex items-center gap-1.5 border-b border-gray-100 bg-gray-50 px-3 py-2"
                                            >
                                                <span
                                                    class="h-2 w-2 rounded-full bg-red-400/80"
                                                />
                                                <span
                                                    class="h-2 w-2 rounded-full bg-yellow-400/80"
                                                />
                                                <span
                                                    class="h-2 w-2 rounded-full bg-green-400/80"
                                                />
                                                <span
                                                    class="ml-2 text-[10px] text-gray-400"
                                                    >correo electrónico</span
                                                >
                                            </div>
                                            <div class="px-3 py-2.5">
                                                <div
                                                    class="mb-1 flex items-center gap-1.5"
                                                >
                                                    <Mail
                                                        class="h-3 w-3 shrink-0 text-[#68c8fb]"
                                                    />
                                                    <p
                                                        class="text-xs font-semibold text-gray-700"
                                                    >
                                                        {{
                                                            selectedTemplateData.subject ||
                                                            'Sin asunto'
                                                        }}
                                                    </p>
                                                </div>
                                                <div class="h-px bg-gray-100" />
                                                <p
                                                    class="mt-2 text-xs leading-relaxed text-gray-500"
                                                >
                                                    {{
                                                        previewBody ??
                                                        'Sin contenido'
                                                    }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Cuerpo completo -->
                                        <div class="space-y-1">
                                            <p
                                                class="text-[10px] font-semibold tracking-wide text-gray-400 uppercase"
                                            >
                                                Contenido
                                            </p>
                                            <div
                                                class="max-h-[140px] overflow-y-auto rounded-xl border border-gray-200 bg-white p-3 text-xs leading-relaxed whitespace-pre-wrap text-gray-600 shadow-sm"
                                            >
                                                {{
                                                    selectedTemplateData.body ||
                                                    'Sin contenido'
                                                }}
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
                                    v-if="
                                        search && filteredTemplates.length > 0
                                    "
                                >
                                    {{ filteredTemplates.length }} de
                                    {{ templates.length }} plantillas
                                </template>
                                <template v-else-if="!search">
                                    {{ templates.length }} plantilla{{
                                        templates.length !== 1 ? 's' : ''
                                    }}
                                    disponible{{
                                        templates.length !== 1 ? 's' : ''
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
                                    :disabled="!selectedTemplate"
                                    @click="attachTemplate"
                                >
                                    <CheckCircle class="h-4 w-4" />
                                    Asociar plantilla
                                </button>
                            </div>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
