<script setup lang="ts">
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import {
    AlignLeft,
    CheckCircle,
    FileText,
    Mail,
    RotateCcw,
    RotateCw,
    Type,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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

const previewBody = computed(() => {
    const firstLine = (form.body ?? '').split('\n')[0].trim();
    if (!firstLine) return 'El cuerpo del mensaje aparecerá aquí...';
    return firstLine.length > 55 ? firstLine.slice(0, 55) + '…' : firstLine;
});

const form = useForm<Template>({
    name: '',
    subject: '',
    body: '',
    is_active: true,
});

watch(
    () => props.template,
    (value) => {
        form.clearErrors();
        if (value) {
            form.name = value.name;
            form.subject = value.subject;
            form.body = value.body;
            form.is_active = Boolean(
                value.is_active === true ||
                value.is_active === 1 ||
                value.is_active === '1',
            );
        } else {
            form.reset();
            form.is_active = true;
        }
    },
    { immediate: true },
);

watch(
    () => props.show,
    (visible) => {
        if (!visible) form.clearErrors();
    },
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

/* ── Editor helpers ─────────────────────────────────────────── */
const bodyRef = ref<HTMLTextAreaElement | null>(null);

// Historial para undo/redo
const history = ref<string[]>(['']);
const historyIdx = ref(0);

const saveHistory = (val: string) => {
    if (val === history.value[historyIdx.value]) return;
    history.value = history.value.slice(0, historyIdx.value + 1);
    history.value.push(val);
    historyIdx.value = history.value.length - 1;
};

const undo = () => {
    if (historyIdx.value <= 0) return;
    historyIdx.value--;
    form.body = history.value[historyIdx.value];
};

const redo = () => {
    if (historyIdx.value >= history.value.length - 1) return;
    historyIdx.value++;
    form.body = history.value[historyIdx.value];
};

const onBodyInput = (e: Event) => {
    const val = (e.target as HTMLTextAreaElement).value;
    form.body = val;
    saveHistory(val);
};

// Inserta texto en la posición del cursor
const insertAtCursor = (text: string) => {
    const el = bodyRef.value;
    if (!el) return;
    const start = el.selectionStart ?? form.body.length;
    const end = el.selectionEnd ?? form.body.length;
    form.body = form.body.slice(0, start) + text + form.body.slice(end);
    saveHistory(form.body);
    // Restaurar foco y cursor tras la inserción
    requestAnimationFrame(() => {
        el.focus();
        el.setSelectionRange(start + text.length, start + text.length);
    });
};

// Envuelve la selección (o inserta marcadores si no hay selección)
const wrapSelection = (before: string, after: string) => {
    const el = bodyRef.value;
    if (!el) return;
    const start = el.selectionStart ?? 0;
    const end = el.selectionEnd ?? 0;
    const sel = form.body.slice(start, end);
    const replacement = sel ? `${before}${sel}${after}` : `${before}${after}`;
    form.body = form.body.slice(0, start) + replacement + form.body.slice(end);
    saveHistory(form.body);
    requestAnimationFrame(() => {
        el.focus();
        const cursor = sel ? start + replacement.length : start + before.length;
        el.setSelectionRange(cursor, cursor);
    });
};

const toUpperCase = () => {
    const el = bodyRef.value;
    if (!el) return;
    const start = el.selectionStart ?? 0;
    const end = el.selectionEnd ?? 0;
    if (start === end) return;
    form.body =
        form.body.slice(0, start) +
        form.body.slice(start, end).toUpperCase() +
        form.body.slice(end);
    saveHistory(form.body);
    requestAnimationFrame(() => {
        el.focus();
        el.setSelectionRange(start, end);
    });
};

const toLowerCase = () => {
    const el = bodyRef.value;
    if (!el) return;
    const start = el.selectionStart ?? 0;
    const end = el.selectionEnd ?? 0;
    if (start === end) return;
    form.body =
        form.body.slice(0, start) +
        form.body.slice(start, end).toLowerCase() +
        form.body.slice(end);
    saveHistory(form.body);
    requestAnimationFrame(() => {
        el.focus();
        el.setSelectionRange(start, end);
    });
};

const charCount = computed(() => form.body.length);
const lineCount = computed(() =>
    form.body ? form.body.split('\n').length : 0,
);

/* ── Vista previa renderizada ───────────────────────────────── */
const bodyRendered = computed(() => {
    if (!form.body)
        return '<span class="text-gray-400 text-xs">La vista previa aparecerá aquí...</span>';

    // 1. Escapar caracteres HTML para evitar inyección
    let html = form.body
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    // 2. Negrita: *texto*
    html = html.replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>');

    // 3. Cursiva: _texto_
    html = html.replace(/_([^_\n]+)_/g, '<em>$1</em>');

    // 4. Placeholders con estilo
    html = html.replace(
        /\{docente\}/g,
        '<span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-semibold bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">{docente}</span>',
    );
    html = html.replace(
        /\{cursos\}/g,
        '<span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-semibold bg-violet-100 text-violet-700 ring-1 ring-violet-200">{cursos}</span>',
    );

    // 5. Saltos de línea
    html = html.replace(/\n/g, '<br>');

    return html;
});
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
                <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" />
            </TransitionChild>

            <div class="fixed inset-0 flex items-center justify-center p-6">
                <TransitionChild
                    enter="duration-500 ease-out"
                    enter-from="opacity-0 scale-95 translate-y-6"
                    enter-to="opacity-100 scale-100 translate-y-0"
                    leave="duration-300 ease-in"
                    leave-from="opacity-100 scale-100 translate-y-0"
                    leave-to="opacity-0 scale-95 translate-y-4"
                    class="relative w-full max-w-4xl"
                >
                    <DialogPanel
                        class="w-full overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                    >
                        <!-- HEADER con preview en vivo -->
                        <div
                            class="relative overflow-hidden bg-gradient-to-br from-orange-400 via-pink-400 to-rose-500 px-6 py-6"
                        >
                            <div
                                class="pointer-events-none absolute -top-12 -right-12 h-52 w-52 rounded-full bg-white/5"
                            />
                            <div
                                class="pointer-events-none absolute right-40 -bottom-8 h-36 w-36 rounded-full bg-white/5"
                            />
                            <div
                                class="pointer-events-none absolute bottom-0 left-1/3 h-24 w-24 rounded-full bg-white/5"
                            />

                            <div class="relative flex items-stretch gap-5">
                                <!-- Título + badge -->
                                <div
                                    class="flex flex-1 flex-col justify-between gap-4"
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
                                                {{
                                                    isEdit
                                                        ? 'Editar Plantilla'
                                                        : 'Nueva Plantilla'
                                                }}
                                            </DialogTitle>
                                            <p class="text-xs text-pink-100">
                                                {{
                                                    isEdit
                                                        ? 'Modifica los datos de la plantilla'
                                                        : 'Completa los campos para crear una nueva plantilla'
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1 transition-all duration-300',
                                                form.is_active
                                                    ? 'bg-emerald-500/20 text-emerald-100 ring-emerald-400/30'
                                                    : 'bg-gray-500/20 text-gray-300 ring-gray-400/30',
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full transition-colors duration-300',
                                                    form.is_active
                                                        ? 'animate-pulse bg-emerald-400'
                                                        : 'bg-gray-400',
                                                ]"
                                            />
                                            {{
                                                form.is_active
                                                    ? 'Plantilla activa'
                                                    : 'Plantilla inactiva'
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Vista previa en vivo -->
                                <div
                                    class="w-64 shrink-0 overflow-hidden rounded-xl bg-white/10 ring-1 ring-white/20"
                                >
                                    <div
                                        class="flex items-center gap-1.5 border-b border-white/10 bg-black/10 px-3 py-2"
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
                                        <span class="ml-2 text-xs text-white/40"
                                            >vista previa</span
                                        >
                                    </div>
                                    <div class="space-y-2 p-3">
                                        <div class="flex items-center gap-1.5">
                                            <Mail
                                                class="h-3 w-3 shrink-0 text-pink-100"
                                            />
                                            <p
                                                class="truncate text-xs font-semibold text-white"
                                            >
                                                {{
                                                    form.subject ||
                                                    'Sin asunto...'
                                                }}
                                            </p>
                                        </div>
                                        <div class="h-px bg-white/10" />
                                        <p
                                            class="truncate text-xs leading-relaxed text-white/60"
                                        >
                                            {{ previewBody }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div class="max-h-[62vh] overflow-y-auto">
                            <div class="space-y-5 p-6">
                                <!-- Fila 1: Nombre + Asunto -->
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Nombre -->
                                    <div class="space-y-1.5">
                                        <label
                                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                        >
                                            <FileText
                                                class="h-3.5 w-3.5 text-pink-400"
                                            />
                                            Nombre de la plantilla
                                            <span
                                                class="ml-auto font-normal text-red-400 normal-case"
                                                >Requerido</span
                                            >
                                        </label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            placeholder="Ej: Bienvenida usuario nuevo"
                                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-pink-400 focus:bg-white focus:ring-2 focus:ring-pink-100 focus:outline-none"
                                            :class="{
                                                'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100':
                                                    form.errors.name,
                                            }"
                                        />
                                        <p
                                            v-if="form.errors.name"
                                            class="text-xs text-red-500"
                                        >
                                            {{ form.errors.name }}
                                        </p>
                                    </div>

                                    <!-- Asunto -->
                                    <div class="space-y-1.5">
                                        <label
                                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                        >
                                            <Mail
                                                class="h-3.5 w-3.5 text-pink-400"
                                            />
                                            Asunto
                                            <span
                                                class="ml-auto font-normal text-red-400 normal-case"
                                                >Requerido</span
                                            >
                                        </label>
                                        <input
                                            v-model="form.subject"
                                            type="text"
                                            placeholder="Ej: Bienvenido a nuestra plataforma"
                                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-pink-400 focus:bg-white focus:ring-2 focus:ring-pink-100 focus:outline-none"
                                            :class="{
                                                'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100':
                                                    form.errors.subject,
                                            }"
                                        />
                                        <p
                                            v-if="form.errors.subject"
                                            class="text-xs text-red-500"
                                        >
                                            {{ form.errors.subject }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Separador -->
                                <div class="relative flex items-center gap-3">
                                    <div class="h-px flex-1 bg-gray-100" />
                                    <span
                                        class="rounded-full border border-gray-100 bg-white px-3 py-0.5 text-xs font-medium tracking-wider text-gray-400 uppercase"
                                    >
                                        Contenido del mensaje
                                    </span>
                                    <div class="h-px flex-1 bg-gray-100" />
                                </div>

                                <!-- Cuerpo + Vista previa lado a lado -->
                                <div class="space-y-1.5">
                                    <!-- Cabecera: label + toggle -->
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <label
                                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                                        >
                                            <AlignLeft
                                                class="h-3.5 w-3.5 text-pink-400"
                                            />
                                            Cuerpo del mensaje
                                            <span
                                                class="ml-2 font-normal text-red-400 normal-case"
                                                >Requerido</span
                                            >
                                        </label>
                                        <div
                                            class="flex items-center gap-2 rounded-xl border border-pink-100 bg-gradient-to-br from-orange-50 to-pink-50 px-3 py-1.5"
                                        >
                                            <CheckCircle
                                                class="h-4 w-4 text-pink-400"
                                            />
                                            <span
                                                class="text-xs font-medium text-gray-600"
                                            >
                                                {{
                                                    form.is_active
                                                        ? 'Activa'
                                                        : 'Inactiva'
                                                }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="
                                                    form.is_active =
                                                        !form.is_active
                                                "
                                                :class="[
                                                    'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:ring-2 focus:ring-pink-400 focus:ring-offset-1 focus:outline-none',
                                                    form.is_active
                                                        ? 'bg-pink-500'
                                                        : 'bg-gray-200',
                                                ]"
                                            >
                                                <span
                                                    :class="[
                                                        'inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-md transition-transform',
                                                        form.is_active
                                                            ? 'translate-x-[18px]'
                                                            : 'translate-x-[3px]',
                                                    ]"
                                                />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Barra de herramientas -->
                                    <div
                                        class="flex flex-wrap items-center gap-1 rounded-t-xl border border-b-0 border-gray-200 bg-gray-50 px-2 py-1.5"
                                    >
                                        <span
                                            class="mr-1 text-[10px] font-semibold tracking-wide text-gray-400 uppercase"
                                            >Insertar:</span
                                        >
                                        <button
                                            type="button"
                                            @click="insertAtCursor('{docente}')"
                                            class="rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 transition hover:bg-indigo-100"
                                            title="Insertar nombre del docente"
                                        >
                                            {docente}
                                        </button>
                                        <button
                                            type="button"
                                            @click="insertAtCursor('{cursos}')"
                                            class="rounded-md border border-violet-200 bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700 transition hover:bg-violet-100"
                                            title="Insertar lista de cursos"
                                        >
                                            {cursos}
                                        </button>

                                        <div
                                            class="mx-1.5 h-4 w-px bg-gray-300"
                                        />

                                        <span
                                            class="mr-1 text-[10px] font-semibold tracking-wide text-gray-400 uppercase"
                                            >Texto:</span
                                        >
                                        <button
                                            type="button"
                                            @click="toUpperCase"
                                            class="rounded-md border border-gray-200 bg-white px-2 py-0.5 text-xs font-semibold text-gray-600 transition hover:bg-gray-100"
                                            title="Convertir selección a MAYÚSCULAS"
                                        >
                                            AA
                                        </button>
                                        <button
                                            type="button"
                                            @click="toLowerCase"
                                            class="rounded-md border border-gray-200 bg-white px-2 py-0.5 text-xs font-medium text-gray-600 transition hover:bg-gray-100"
                                            title="Convertir selección a minúsculas"
                                        >
                                            aa
                                        </button>
                                        <button
                                            type="button"
                                            @click="wrapSelection('*', '*')"
                                            class="rounded-md border border-gray-200 bg-white px-2 py-0.5 text-xs font-bold text-gray-600 transition hover:bg-gray-100"
                                            title="Negrita: envuelve la selección con *asteriscos*"
                                        >
                                            B
                                        </button>
                                        <button
                                            type="button"
                                            @click="wrapSelection('_', '_')"
                                            class="rounded-md border border-gray-200 bg-white px-2 py-0.5 text-xs text-gray-600 italic transition hover:bg-gray-100"
                                            title="Cursiva: envuelve la selección con _guiones bajos_"
                                        >
                                            I
                                        </button>

                                        <div
                                            class="mx-1.5 h-4 w-px bg-gray-300"
                                        />

                                        <button
                                            type="button"
                                            @click="undo"
                                            :disabled="historyIdx <= 0"
                                            class="rounded-md border border-gray-200 bg-white p-1 text-gray-600 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                                            title="Deshacer"
                                        >
                                            <RotateCcw class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="redo"
                                            :disabled="
                                                historyIdx >= history.length - 1
                                            "
                                            class="rounded-md border border-gray-200 bg-white p-1 text-gray-600 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                                            title="Rehacer"
                                        >
                                            <RotateCw class="h-3.5 w-3.5" />
                                        </button>

                                        <div
                                            class="ml-auto flex items-center gap-2 text-[10px] text-gray-400"
                                        >
                                            <Type class="h-3 w-3" />
                                            <span
                                                >{{ charCount }} car. ·
                                                {{ lineCount }} líneas</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Editor + Preview lado a lado -->
                                    <div
                                        class="grid grid-cols-2 overflow-hidden rounded-b-xl border border-gray-200"
                                    >
                                        <!-- Textarea -->
                                        <textarea
                                            ref="bodyRef"
                                            :value="form.body"
                                            @input="onBodyInput"
                                            rows="10"
                                            placeholder="Escribe el contenido principal del mensaje...&#10;&#10;Usa {docente} para el nombre del docente y {cursos} para la lista de cursos."
                                            class="h-full w-full resize-none border-r border-gray-200 bg-white px-3.5 py-2.5 font-mono text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-pink-400 focus:ring-2 focus:ring-pink-100 focus:outline-none"
                                            :class="{
                                                'bg-red-50': form.errors.body,
                                            }"
                                        />

                                        <!-- Panel de vista previa renderizada -->
                                        <div class="flex flex-col bg-gray-50">
                                            <div
                                                class="flex items-center gap-1.5 border-b border-gray-200 bg-white px-3 py-1.5"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-pink-400"
                                                />
                                                <span
                                                    class="text-[10px] font-semibold tracking-wide text-gray-400 uppercase"
                                                    >Vista previa</span
                                                >
                                            </div>
                                            <div
                                                class="h-full overflow-y-auto px-3.5 py-2.5 text-sm leading-relaxed text-gray-800"
                                                v-html="bodyRendered"
                                            />
                                        </div>
                                    </div>

                                    <p
                                        v-if="form.errors.body"
                                        class="text-xs text-red-500"
                                    >
                                        {{ form.errors.body }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div
                            class="flex items-center justify-between border-t border-gray-100 bg-gray-50/70 px-6 py-4"
                        >
                            <p class="text-xs text-gray-400">
                                <span class="text-red-400">*</span> Los campos
                                marcados son requeridos
                            </p>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                                    @click="emit('close')"
                                >
                                    Cerrar
                                </button>
                                <button
                                    type="button"
                                    class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-orange-400 to-pink-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-pink-200 transition-all hover:from-orange-300 hover:to-pink-400 hover:shadow-lg hover:shadow-pink-300 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="form.processing"
                                    @click="submit"
                                >
                                    <svg
                                        v-if="form.processing"
                                        class="h-4 w-4 animate-spin"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        />
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                        />
                                    </svg>
                                    {{
                                        form.processing
                                            ? 'Guardando...'
                                            : isEdit
                                              ? 'Actualizar plantilla'
                                              : 'Crear plantilla'
                                    }}
                                </button>
                            </div>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
