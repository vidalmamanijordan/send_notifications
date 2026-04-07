<script setup lang="ts">
import { Mail, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    show: boolean;
    subject: string;
    body: string;
    officeName: string;
    officeEmail: string;
    officeSignature?: string;
    sentAt: string;
    emails?: string[];
    teachers?: { id: number; name: string; email: string }[];
}>();

const emit = defineEmits(['close']);

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('es-PE', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
};

const initial = computed(
    () => props.officeName?.charAt(0).toUpperCase() ?? 'O',
);

const emailsText = computed(() => {
    if (!props.emails || props.emails.length === 0) {
        return 'Docentes';
    }

    if (props.emails.length <= 3) {
        return props.emails.join(', ');
    }

    return `${props.emails.slice(0, 3).join(', ')} y ${props.emails.length - 3} más`;
});

/* Aplica el mismo renderizado que el editor de plantillas */
const renderText = (text: string): string => {
    let html = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    html = html.replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>');
    html = html.replace(/_([^_\n]+)_/g, '<em>$1</em>');

    // Líneas de curso: "- Nombre (Ciclo: X, Grupo: Y)"
    html = html
        .split('\n')
        .map((line) => {
            if (/^- .+\(Ciclo: .+, Grupo: .+\)/.test(line)) {
                return (
                    `<span style="display:inline-flex;align-items:center;gap:6px;` +
                    `background:#f0f7ff;border-left:3px solid #0078d4;` +
                    `border-radius:4px;padding:2px 8px;margin:1px 0;` +
                    `font-size:0.8rem;color:#0078d4;font-weight:500;">` +
                    `${line.replace(/^- /, '')}</span>`
                );
            }
            return line;
        })
        .join('\n');

    html = html.replace(/\n/g, '<br>');

    return html;
};

/* 👇 BODY + FIRMA */
const fullBody = computed(() => {
    let html = props.body ? renderText(props.body.replace(/\n+$/g, '')) : '';

    if (props.officeSignature) {
        html += `<div style="margin-top:10px;"><img src="/storage/${props.officeSignature}" style="max-height:90px; object-fit:contain;" /></div>`;
    }

    return html;
});

const truncateName = (name: string, max = 20) => {
    return name.length > max ? name.slice(0, max) + '...' : name;
};
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
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-6 backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <Transition
                enter-active-class="duration-300 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-4"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="duration-200 ease-in"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-95 translate-y-4"
                appear
            >
                <div
                    v-if="show"
                    class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                >
                    <!-- HEADER con gradiente -->
                    <div
                        class="relative overflow-hidden bg-gradient-to-br from-orange-400 via-pink-400 to-rose-500 px-6 py-5"
                    >
                        <!-- Círculos decorativos -->
                        <div
                            class="pointer-events-none absolute -top-10 -right-10 h-44 w-44 rounded-full bg-white/5"
                        />
                        <div
                            class="pointer-events-none absolute right-36 -bottom-6 h-28 w-28 rounded-full bg-white/5"
                        />
                        <div
                            class="pointer-events-none absolute bottom-0 left-1/3 h-20 w-20 rounded-full bg-white/5"
                        />

                        <div class="relative flex items-center gap-5">
                            <!-- Icono + título + subtítulo -->
                            <div class="flex flex-1 items-center gap-3">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25"
                                >
                                    <Mail class="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <p class="text-base font-bold text-white">
                                        Vista previa del correo
                                    </p>
                                    <p class="text-xs text-pink-100">
                                        {{ officeName }} &lt;{{
                                            officeEmail
                                        }}&gt;
                                    </p>
                                </div>
                            </div>

                            <!-- Tarjeta mini de asunto -->
                            <div
                                class="w-56 shrink-0 overflow-hidden rounded-xl bg-white/10 ring-1 ring-white/20"
                            >
                                <div
                                    class="flex items-center gap-1.5 border-b border-white/10 bg-black/10 px-3 py-1.5"
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
                                        >asunto</span
                                    >
                                </div>
                                <div class="px-3 py-2">
                                    <p
                                        class="truncate text-xs font-semibold text-white"
                                    >
                                        {{ subject || 'Sin asunto' }}
                                    </p>
                                    <p class="mt-0.5 text-[10px] text-white/50">
                                        {{ formatDate(sentAt) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CONTENIDO -->
                    <div class="flex-1 overflow-y-auto px-8 py-6">
                        <!-- REMITENTE -->
                        <div class="flex items-start gap-4">
                            <!-- AVATAR -->
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#0078d4] text-sm font-semibold text-white shadow"
                            >
                                {{ initial }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <!-- NOMBRE / CORREO / FECHA -->
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-semibold text-gray-900"
                                            >{{ officeName }}</span
                                        >
                                        <span class="text-xs text-gray-500"
                                            >&lt;{{ officeEmail }}&gt;</span
                                        >
                                    </div>
                                    <span
                                        class="shrink-0 text-xs text-gray-400"
                                        >{{ formatDate(sentAt) }}</span
                                    >
                                </div>

                                <!-- CHIPS DE DOCENTES -->
                                <div
                                    class="mt-2 flex items-start gap-2 text-sm"
                                >
                                    <span
                                        class="mt-[3px] shrink-0 text-xs text-gray-500"
                                        >Para:</span
                                    >
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-if="
                                                !teachers ||
                                                teachers.length === 0
                                            "
                                            class="flex items-center gap-1 rounded-md bg-gray-100 px-2 py-[3px] text-xs font-medium text-gray-700 transition hover:bg-gray-200"
                                        >
                                            <XCircle
                                                class="h-3 w-3 text-gray-400"
                                            />
                                            Docentes
                                        </span>
                                        <span
                                            v-else
                                            v-for="teacher in teachers"
                                            :key="teacher.id"
                                            class="flex items-center gap-1 rounded-md bg-[#e8f3fb] px-2 py-[3px] text-xs font-medium text-[#0078d4] ring-1 ring-[#b3d6f0] transition hover:bg-[#d0e8f8]"
                                        >
                                            <XCircle
                                                class="h-3 w-3 text-[#0078d4]"
                                            />
                                            {{ truncateName(teacher.name) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- ASUNTO -->
                                <div class="mt-5 border-b border-gray-100 pb-3">
                                    <span
                                        class="text-base font-semibold text-gray-900"
                                        >{{ subject }}</span
                                    >
                                </div>

                                <!-- BODY + FIRMA -->
                                <div
                                    class="mt-4 max-h-[42vh] overflow-y-auto rounded-xl bg-gray-50 p-4 text-sm leading-relaxed text-gray-800"
                                    v-html="fullBody"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div
                        class="flex justify-end border-t border-gray-100 bg-gray-50/70 px-6 py-3"
                    >
                        <button
                            type="button"
                            @click="emit('close')"
                            class="rounded-xl border border-gray-200 bg-white px-5 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
