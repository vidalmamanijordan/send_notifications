<script setup lang="ts">
import { FileText, Mail, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Template {
    id: number;
    name: string;
    subject: string;
    body: string;
    is_active: boolean;
}

const props = defineProps<{
    show: boolean;
    template: Template | null;
}>();

const emit = defineEmits(['close']);

const loading = ref(false);
const error = ref<string | null>(null);
const previewHtml = ref('');
const previewSubject = ref('');

watch(
    () => [props.show, props.template],
    async ([show, template]) => {
        if (!show || !template) return;

        loading.value = true;
        error.value = null;
        previewHtml.value = '';
        previewSubject.value = '';

        try {
            const res = await fetch(
                route('admin.notification-templates.preview', (template as Template).id),
                { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } },
            );

            if (!res.ok) throw new Error('Error al cargar la vista previa');

            const data = await res.json();
            previewHtml.value = data.html ?? '';
            previewSubject.value = data.subject ?? '';
        } catch {
            error.value = 'No se pudo cargar la vista previa del correo.';
        } finally {
            loading.value = false;
        }
    },
    { immediate: true },
);
</script>

<template>
    <Transition
        enter-active-class="duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-[1px]"
            @click.self="emit('close')"
        >
            <Transition
                enter-active-class="duration-400 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-6"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="duration-250 ease-in"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-95 translate-y-4"
                appear
            >
                <div
                    v-if="show"
                    class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                >
                    <!-- HEADER -->
                    <div class="relative shrink-0 overflow-hidden bg-linear-to-br from-[#087ab1] to-[#68c8fb] px-6 py-5">
                        <div class="pointer-events-none absolute -top-12 -right-12 h-52 w-52 rounded-full bg-white/8" />
                        <div class="pointer-events-none absolute right-40 -bottom-8 h-36 w-36 rounded-full bg-white/5" />
                        <div class="pointer-events-none absolute -top-2 right-28 h-[160%] w-px rotate-22 rounded-full bg-white/20" />
                        <div class="pointer-events-none absolute -top-2 right-20 h-[160%] w-px rotate-22 rounded-full bg-white/14" />
                        <div class="pointer-events-none absolute -top-2 right-12 h-[160%] w-px rotate-22 rounded-full bg-white/8" />

                        <div class="relative flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                    <Mail class="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <p class="text-base font-bold text-white">Vista previa del correo</p>
                                    <p class="truncate text-xs text-[#68c8fb]">
                                        {{ template?.name ?? 'Plantilla' }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="emit('close')"
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 text-white transition hover:bg-white/25"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- CUERPO -->
                    <div class="flex min-h-0 flex-1 flex-col bg-[#f3f4f6]">
                        <!-- Barra browser -->
                        <div class="flex shrink-0 items-center gap-2 border-b border-gray-200 bg-[#e9eaec] px-4 py-2.5">
                            <span class="h-3 w-3 rounded-full bg-red-400/80" />
                            <span class="h-3 w-3 rounded-full bg-yellow-400/80" />
                            <span class="h-3 w-3 rounded-full bg-green-400/80" />
                            <div class="mx-2 flex flex-1 items-center gap-2 rounded-md border border-gray-300/60 bg-white px-3 py-1 shadow-sm">
                                <Mail class="h-3 w-3 shrink-0 text-[#087ab1]" />
                                <span class="truncate text-[11px] text-gray-400">
                                    {{ previewSubject || 'Vista previa del correo' }}
                                </span>
                            </div>
                            <span class="rounded-md bg-[#087ab1]/10 px-2 py-0.5 text-[10px] font-semibold tracking-wide text-[#087ab1] uppercase">
                                Preview
                            </span>
                        </div>

                        <!-- Loading -->
                        <div
                            v-if="loading"
                            class="flex flex-1 flex-col items-center justify-center gap-3 py-16"
                        >
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#087ab1]/10">
                                <FileText class="h-6 w-6 animate-pulse text-[#087ab1]" />
                            </div>
                            <p class="text-sm font-medium text-gray-500">Generando vista previa...</p>
                        </div>

                        <!-- Error -->
                        <div
                            v-else-if="error"
                            class="flex flex-1 items-center justify-center py-16"
                        >
                            <p class="text-sm text-red-500">{{ error }}</p>
                        </div>

                        <!-- Iframe -->
                        <div v-else class="flex-1 overflow-auto">
                            <iframe
                                :srcdoc="previewHtml"
                                class="h-full w-full border-0 bg-white"
                                style="min-height: 480px"
                                sandbox="allow-same-origin"
                            />
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="flex shrink-0 items-center justify-end border-t border-gray-100 bg-gray-50/70 px-6 py-4">
                        <button
                            type="button"
                            @click="emit('close')"
                            class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
