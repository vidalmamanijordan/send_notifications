<script setup lang="ts">
import { Mail, Users, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    show: boolean;
    subject: string;
    html: string;
    emails?: string[];
    teachers?: { id: number; name: string; email: string }[];
}>();

const emit = defineEmits(['close']);

const VISIBLE_LIMIT = 10;
const showAllTeachers = ref(false);

const visibleTeachers = computed(() =>
    showAllTeachers.value
        ? (props.teachers ?? [])
        : (props.teachers ?? []).slice(0, VISIBLE_LIMIT),
);

const hiddenCount = computed(() =>
    Math.max(0, (props.teachers?.length ?? 0) - VISIBLE_LIMIT),
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
                    class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                >
                    <!-- ══════════════════════════════════
                         HEADER
                    ═══════════════════════════════════ -->
                    <div class="relative shrink-0 overflow-hidden bg-linear-to-br from-[#087ab1] to-[#68c8fb] px-6 py-5">
                        <div class="pointer-events-none absolute -top-12 -right-12 h-52 w-52 rounded-full bg-white/8" />
                        <div class="pointer-events-none absolute right-48 -bottom-10 h-40 w-40 rounded-full bg-white/5" />
                        <div class="pointer-events-none absolute -top-2 right-32 h-[160%] w-px rotate-22 rounded-full bg-white/20" />
                        <div class="pointer-events-none absolute -top-2 right-24 h-[160%] w-px rotate-22 rounded-full bg-white/14" />
                        <div class="pointer-events-none absolute -top-2 right-16 h-[160%] w-px rotate-22 rounded-full bg-white/8" />

                        <div class="relative flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                    <Mail class="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <p class="text-base font-bold text-white">Vista previa del correo</p>
                                    <p class="text-xs text-[#68c8fb]">Así verán el mensaje los docentes</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div
                                    v-if="teachers && teachers.length > 0"
                                    class="flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 ring-1 ring-white/20"
                                >
                                    <Users class="h-4 w-4 text-white/70" />
                                    <span class="text-sm font-bold text-white">{{ teachers.length }}</span>
                                    <span class="text-[10px] text-white/60">
                                        destinatario{{ teachers.length !== 1 ? 's' : '' }}
                                    </span>
                                </div>

                                <button
                                    @click="emit('close')"
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 text-white transition hover:bg-white/25"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ══════════════════════════════════
                         CUERPO: sidebar + preview
                    ═══════════════════════════════════ -->
                    <div class="flex min-h-0 flex-1">

                        <!-- SIDEBAR IZQUIERDA -->
                        <div class="flex w-72 shrink-0 flex-col overflow-y-auto border-r border-gray-100 bg-gray-50/60">

                            <!-- Asunto -->
                            <div class="border-b border-gray-100 px-5 py-4">
                                <p class="mb-1 text-[10px] font-semibold tracking-wider text-gray-400 uppercase">Asunto</p>
                                <p class="text-sm font-semibold text-gray-800 leading-snug">
                                    {{ subject || 'Sin asunto' }}
                                </p>
                            </div>

                            <!-- Para -->
                            <div class="flex-1 px-5 py-4">
                                <p class="mb-3 text-[10px] font-semibold tracking-wider text-gray-400 uppercase">
                                    Para
                                    <span v-if="teachers && teachers.length > 0" class="ml-1 rounded-full bg-[#087ab1]/10 px-1.5 py-0.5 text-[9px] font-bold text-[#087ab1]">
                                        {{ teachers.length }}
                                    </span>
                                </p>

                                <!-- Sin destinatarios -->
                                <div
                                    v-if="!teachers || teachers.length === 0"
                                    class="flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-2 text-xs text-gray-500"
                                >
                                    <Users class="h-3.5 w-3.5 text-gray-400" />
                                    Sin destinatarios definidos
                                </div>

                                <!-- Chips estilo Outlook -->
                                <div v-else class="flex flex-wrap gap-1.5">
                                    <div
                                        v-for="teacher in visibleTeachers"
                                        :key="teacher.id"
                                        class="group relative flex h-6 items-center gap-1.5 rounded-full bg-[#e8f1f8] px-2 text-xs text-[#1f3864] transition hover:bg-[#cde1f3]"
                                    >
                                        <!-- Círculo con X -->
                                        <span class="flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full border border-[#0099cc] text-[#0099cc] transition group-hover:border-[#0078d4] group-hover:text-[#0078d4]">
                                            <X class="h-2 w-2" />
                                        </span>

                                        <!-- Nombre -->
                                        <span class="max-w-[140px] truncate font-medium leading-none">
                                            {{ teacher.name }}
                                        </span>

                                        <!-- Tooltip -->
                                        <div class="pointer-events-none absolute top-7 left-0 z-20 hidden min-w-[200px] rounded border border-gray-200 bg-white p-2.5 shadow-xl group-hover:block">
                                            <span class="block text-xs font-semibold text-gray-800">{{ teacher.name }}</span>
                                            <span class="block text-[10px] text-gray-400">{{ teacher.email }}</span>
                                        </div>
                                    </div>

                                    <button
                                        v-if="!showAllTeachers && hiddenCount > 0"
                                        @click="showAllTeachers = true"
                                        class="h-6 rounded-full bg-gray-100 px-2.5 text-xs font-semibold text-gray-500 transition hover:bg-[#e8f1f8] hover:text-[#0078d4]"
                                    >
                                        +{{ hiddenCount }} más
                                    </button>
                                    <button
                                        v-if="showAllTeachers && hiddenCount > 0"
                                        @click="showAllTeachers = false"
                                        class="h-6 rounded-full bg-gray-100 px-2.5 text-xs font-semibold text-gray-500 transition hover:bg-gray-200"
                                    >
                                        Ver menos
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- PREVIEW DERECHA -->
                        <div class="flex min-w-0 flex-1 flex-col bg-[#f3f4f6]">
                            <!-- Barra browser -->
                            <div class="flex shrink-0 items-center gap-2 border-b border-gray-200 bg-[#e9eaec] px-4 py-2.5">
                                <span class="h-3 w-3 rounded-full bg-red-400/80" />
                                <span class="h-3 w-3 rounded-full bg-yellow-400/80" />
                                <span class="h-3 w-3 rounded-full bg-green-400/80" />

                                <div class="mx-2 flex flex-1 items-center gap-2 rounded-md border border-gray-300/60 bg-white px-3 py-1 shadow-sm">
                                    <Mail class="h-3 w-3 shrink-0 text-[#087ab1]" />
                                    <span class="truncate text-[11px] text-gray-400">
                                        {{ subject || 'Vista previa del correo' }}
                                    </span>
                                </div>

                                <span class="rounded-md bg-[#087ab1]/10 px-2 py-0.5 text-[10px] font-semibold tracking-wide text-[#087ab1] uppercase">
                                    Preview
                                </span>
                            </div>

                            <!-- Iframe -->
                            <div class="flex-1 overflow-auto">
                                <iframe
                                    :srcdoc="html"
                                    class="h-full w-full border-0 bg-white"
                                    style="min-height: 420px"
                                    sandbox="allow-same-origin"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- ══════════════════════════════════
                         FOOTER
                    ═══════════════════════════════════ -->
                    <div class="flex shrink-0 items-center justify-between border-t border-gray-100 bg-gray-50/70 px-6 py-4">
                        <p class="text-xs text-gray-400">
                            <template v-if="teachers && teachers.length > 0">
                                Este correo será enviado a
                                <span class="font-semibold text-gray-600">
                                    {{ teachers.length }} docente{{ teachers.length !== 1 ? 's' : '' }}
                                </span>
                            </template>
                            <template v-else>Sin destinatarios definidos</template>
                        </p>
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
