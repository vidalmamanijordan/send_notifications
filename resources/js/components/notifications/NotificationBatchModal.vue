<script setup lang="ts">
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import {
    BookOpen,
    Building2,
    CheckCircle2,
    Mail,
    RotateCcw,
    Send,
    Users,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Teacher {
    full_name: string;
    dni?: string | null;
}

interface Detail {
    id: number;
    pending_courses_count: number;
    status_label: string;
    status?: string;
    has_email: boolean;
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
    status: string;
    status_label: string;
    teachers_count: number;
    total_pending_courses: number;
    sent_count: number;
    failed_count: number;
    skipped_count: number;
    pending_count: number;
    academic_period?: { name: string };
    campus?: { name: string };
    office?: {
        id: number;
        name: string;
        email: string;
        signature?: string | null;
    } | null;
    details: {
        data: Detail[];
        links: PaginationLink[];
    };
}

const props = defineProps<{
    show: boolean;
    batch: BatchResponse | null;
    sending?: boolean;
}>();

const emit = defineEmits(['close', 'paginate', 'send', 'resend']);

/* ── Helpers ── */

const statusBadgeClass = (status: string) => {
    switch (status) {
        case 'draft':        return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
        case 'active':       return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
        case 'processing':   return 'bg-blue-50 text-blue-700 ring-1 ring-blue-200 animate-pulse';
        case 'completed':    return 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300';
        case 'completed_with_errors': return 'bg-orange-50 text-orange-700 ring-1 ring-orange-300';
        case 'failed':       return 'bg-rose-50 text-rose-700 ring-1 ring-rose-300';
        default:             return 'bg-gray-50 text-gray-700 ring-1 ring-gray-200';
    }
};

const detailStatusClass = (status: string) => {
    switch (status) {
        case 'Pendiente': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
        case 'Enviado':   return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
        case 'Fallido':   return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
        case 'Sin correo':return 'bg-gray-100 text-gray-500 ring-1 ring-gray-200';
        default:          return 'bg-gray-100 text-gray-700';
    }
};

const getInitials = (name: string) =>
    name.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase();

const avatarColor = (name: string) => {
    const colors = [
        'bg-indigo-500', 'bg-violet-500', 'bg-sky-500',
        'bg-emerald-500', 'bg-amber-500', 'bg-rose-500',
        'bg-teal-500', 'bg-fuchsia-500',
    ];
    return colors[(name.charCodeAt(0) ?? 0) % colors.length];
};

const isPageNumber = (label: string) =>
    /^\d+$/.test(label.replace(/&[^;]+;/g, '').trim());

const goToPage = (url: string | null) => {
    if (!url) return;
    emit('paginate', url);
};

/* ── Progress bar ── */
const sentPercent = computed(() => {
    const total = props.batch?.teachers_count ?? 0;
    if (!total) return 0;
    return Math.round(((props.batch?.sent_count ?? 0) / total) * 100);
});

const canSend = computed(() =>
    props.batch?.status === 'active' || props.batch?.status === 'draft',
);
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

            <div class="fixed inset-0 flex items-center justify-center p-4">
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
                        class="flex max-h-[90vh] w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                    >
                        <!-- ═══════════════════════════════════════
                             HEADER
                        ════════════════════════════════════════ -->
                        <div
                            class="relative overflow-hidden bg-linear-to-br from-[#087ab1] to-[#68c8fb] px-6 py-5"
                        >
                            <!-- Decoraciones -->
                            <div class="pointer-events-none absolute -top-12 -right-12 h-52 w-52 rounded-full bg-white/8" />
                            <div class="pointer-events-none absolute right-48 -bottom-10 h-40 w-40 rounded-full bg-white/5" />
                            <div class="pointer-events-none absolute -top-2 right-32 h-[160%] w-px rotate-22 rounded-full bg-white/20" />
                            <div class="pointer-events-none absolute -top-2 right-24 h-[160%] w-px rotate-22 rounded-full bg-white/14" />
                            <div class="pointer-events-none absolute -top-2 right-16 h-[160%] w-px rotate-22 rounded-full bg-white/8" />

                            <div class="relative flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                        <Send class="h-5 w-5 text-white" />
                                    </div>
                                    <div>
                                        <DialogTitle class="text-base font-bold text-white">
                                            Notificación Rubros Vencidos
                                        </DialogTitle>
                                        <p class="text-xs text-[#68c8fb]">
                                            {{ batch?.academic_period?.name ?? '—' }}
                                            <span class="mx-1 opacity-50">·</span>
                                            {{ batch?.campus?.name ?? '—' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2.5">
                                    <span
                                        v-if="batch?.status"
                                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="statusBadgeClass(batch.status)"
                                    >
                                        <span
                                            v-if="batch.status === 'processing'"
                                            class="mr-1.5 h-1.5 w-1.5 animate-ping rounded-full bg-blue-500"
                                        />
                                        {{ batch.status_label }}
                                    </span>
                                    <button
                                        @click="emit('close')"
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 text-white transition hover:bg-white/25"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════
                             BARRA DE CONTEXTO: Oficina remitente
                        ════════════════════════════════════════ -->
                        <div
                            v-if="batch?.office"
                            class="flex items-center gap-4 border-b border-gray-100 bg-gray-50/80 px-6 py-3"
                        >
                            <div class="flex items-center gap-3">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#087ab1]/10">
                                    <Building2 class="h-3.5 w-3.5 text-[#087ab1]" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-700">
                                        {{ batch.office.name }}
                                    </p>
                                    <p class="text-[10px] text-gray-400">
                                        {{ batch.office.email }}
                                    </p>
                                </div>
                            </div>

                            <div class="mx-3 h-8 w-px bg-gray-200" />

                            <!-- Firma miniatura -->
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-semibold tracking-wide text-gray-400 uppercase">
                                    Firma
                                </span>
                                <img
                                    v-if="batch.office.signature"
                                    :src="`/storage/${batch.office.signature}`"
                                    :alt="`Firma ${batch.office.name}`"
                                    class="h-7 max-w-[100px] rounded border border-gray-200 bg-white object-contain p-0.5"
                                />
                                <span v-else class="text-[10px] italic text-gray-400">
                                    Sin firma
                                </span>
                            </div>

                            <!-- Progreso de envío -->
                            <div class="ml-auto flex items-center gap-3">
                                <div class="w-28">
                                    <div class="mb-1 flex justify-between text-[10px] text-gray-400">
                                        <span>Progreso</span>
                                        <span class="font-semibold text-[#087ab1]">{{ sentPercent }}%</span>
                                    </div>
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                        <div
                                            class="h-full rounded-full bg-linear-to-r from-[#087ab1] to-[#68c8fb] transition-all duration-500"
                                            :style="{ width: `${sentPercent}%` }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════
                             KPIs
                        ════════════════════════════════════════ -->
                        <div class="grid grid-cols-4 gap-0 border-b border-gray-100">
                            <!-- Total docentes -->
                            <div class="flex items-center gap-3 border-r border-gray-100 px-5 py-4">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#087ab1]/10">
                                    <Users class="h-4 w-4 text-[#087ab1]" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wide">
                                        Docentes
                                    </p>
                                    <p class="text-xl font-bold text-gray-800">
                                        {{ batch?.teachers_count ?? 0 }}
                                    </p>
                                </div>
                            </div>

                            <!-- Enviados -->
                            <div class="flex items-center gap-3 border-r border-gray-100 px-5 py-4">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100">
                                    <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wide">
                                        Enviados
                                    </p>
                                    <p class="text-xl font-bold text-emerald-600">
                                        {{ batch?.sent_count ?? 0 }}
                                    </p>
                                </div>
                            </div>

                            <!-- Fallidos -->
                            <div class="flex items-center gap-3 border-r border-gray-100 px-5 py-4">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-100">
                                    <XCircle class="h-4 w-4 text-rose-500" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wide">
                                        Fallidos
                                    </p>
                                    <p class="text-xl font-bold text-rose-600">
                                        {{ batch?.failed_count ?? 0 }}
                                    </p>
                                </div>
                            </div>

                            <!-- Cursos vencidos -->
                            <div class="flex items-center gap-3 px-5 py-4">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100">
                                    <BookOpen class="h-4 w-4 text-amber-600" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wide">
                                        Cursos
                                    </p>
                                    <p class="text-xl font-bold text-amber-600">
                                        {{ batch?.total_pending_courses ?? 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════
                             TABLA DE DETALLE
                        ════════════════════════════════════════ -->
                        <div class="flex-1 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-linear-to-r from-gray-50 to-gray-100">
                                        <th class="w-10 px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                            #
                                        </th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                            Docente
                                        </th>
                                        <th class="px-5 py-3 text-center text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                            Cursos vencidos
                                        </th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                            Estado
                                        </th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                            Acción
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="(d, index) in batch?.details.data ?? []"
                                        :key="d.id"
                                        class="transition-all duration-150 hover:bg-[#68c8fb]/2 hover:shadow-[inset_3px_0_0_#087ab1]"
                                    >
                                        <!-- Número -->
                                        <td class="px-5 py-3.5 text-sm text-gray-400">
                                            {{ index + 1 }}
                                        </td>

                                        <!-- Docente -->
                                        <td class="px-5 py-3.5">
                                            <div class="flex items-center gap-2.5">
                                                <div
                                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
                                                    :class="avatarColor(d.teacher?.full_name ?? 'X')"
                                                >
                                                    {{ getInitials(d.teacher?.full_name ?? 'ND') }}
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-800">
                                                        {{ d.teacher?.full_name ?? 'No disponible' }}
                                                    </p>
                                                    <div class="mt-0.5 flex items-center gap-2">
                                                        <span
                                                            v-if="d.teacher?.dni"
                                                            class="rounded bg-[#087ab1]/8 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-[#087ab1]"
                                                        >
                                                            {{ d.teacher.dni }}
                                                        </span>
                                                        <div class="flex items-center gap-1">
                                                            <Mail
                                                                v-if="d.has_email"
                                                                class="h-2.5 w-2.5 text-[#087ab1]"
                                                            />
                                                            <XCircle
                                                                v-else
                                                                class="h-2.5 w-2.5 text-rose-400"
                                                            />
                                                            <span
                                                                class="text-[10px]"
                                                                :class="d.has_email ? 'text-gray-400' : 'text-rose-400'"
                                                            >
                                                                {{ d.has_email ? 'Con correo' : 'Sin correo' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Cursos vencidos -->
                                        <td class="px-5 py-3.5 text-center">
                                            <span
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                                                :class="
                                                    d.pending_courses_count > 0
                                                        ? 'bg-amber-100 text-amber-700'
                                                        : 'bg-gray-100 text-gray-400'
                                                "
                                            >
                                                {{ d.pending_courses_count }}
                                            </span>
                                        </td>

                                        <!-- Estado -->
                                        <td class="px-5 py-3.5">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                :class="detailStatusClass(d.status_label)"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full"
                                                    :class="{
                                                        'bg-amber-500 animate-pulse': d.status_label === 'Pendiente',
                                                        'bg-emerald-500': d.status_label === 'Enviado',
                                                        'bg-rose-500': d.status_label === 'Fallido',
                                                        'bg-gray-400': d.status_label === 'Sin correo',
                                                    }"
                                                />
                                                {{ d.status_label }}
                                            </span>
                                        </td>

                                        <!-- Acción -->
                                        <td class="px-5 py-3.5 text-right">
                                            <button
                                                v-if="(d.status === 'failed' || d.status === 'skipped') && d.has_email"
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium text-[#087ab1] ring-1 ring-[#087ab1]/30 transition hover:bg-[#087ab1] hover:text-white"
                                                @click="emit('resend', d.id)"
                                            >
                                                <RotateCcw class="h-3 w-3" />
                                                Reenviar
                                            </button>
                                            <span v-else class="text-xs text-gray-300">—</span>
                                        </td>
                                    </tr>

                                    <!-- Vacío -->
                                    <tr v-if="(batch?.details.data ?? []).length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center gap-3">
                                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#087ab1]/10">
                                                    <Users class="h-6 w-6 text-[#087ab1]/40" />
                                                </div>
                                                <p class="text-sm text-gray-400">
                                                    No hay docentes en este lote
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- ═══════════════════════════════════════
                             PAGINACIÓN
                        ════════════════════════════════════════ -->
                        <div
                            v-if="(batch?.details.links ?? []).length > 3"
                            class="flex items-center justify-center gap-1 border-t border-gray-100 px-5 py-3"
                        >
                            <template
                                v-for="(link, i) in batch?.details.links ?? []"
                                :key="i"
                            >
                                <button
                                    v-if="link.url"
                                    v-html="link.label"
                                    @click="goToPage(link.url)"
                                    class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-all"
                                    :class="
                                        link.active && isPageNumber(link.label)
                                            ? 'border-[#68c8fb] bg-[#68c8fb] text-white shadow-sm'
                                            : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300 hover:bg-gray-50'
                                    "
                                />
                                <span
                                    v-else
                                    v-html="link.label"
                                    class="cursor-not-allowed rounded-lg border border-gray-100 bg-white px-3 py-1.5 text-sm font-medium text-gray-300"
                                />
                            </template>
                        </div>

                        <!-- ═══════════════════════════════════════
                             FOOTER
                        ════════════════════════════════════════ -->
                        <div
                            class="flex items-center justify-between border-t border-gray-100 bg-gray-50/70 px-6 py-4"
                        >
                            <div class="flex items-center gap-2 text-xs text-gray-400">
                                <span>
                                    {{ batch?.sent_count ?? 0 }} de
                                    {{ batch?.teachers_count ?? 0 }} enviados
                                </span>
                                <template v-if="(batch?.failed_count ?? 0) > 0">
                                    <span class="text-gray-300">·</span>
                                    <span class="text-rose-500">
                                        {{ batch?.failed_count }} fallido{{ (batch?.failed_count ?? 0) !== 1 ? 's' : '' }}
                                    </span>
                                </template>
                                <template v-if="(batch?.skipped_count ?? 0) > 0">
                                    <span class="text-gray-300">·</span>
                                    <span class="text-gray-500">
                                        {{ batch?.skipped_count }} sin correo
                                    </span>
                                </template>
                            </div>

                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                                    @click="emit('close')"
                                >
                                    Cerrar
                                </button>
                                <button
                                    v-if="canSend"
                                    type="button"
                                    class="flex items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#087ab1]/30 transition-all hover:opacity-90 hover:shadow-lg hover:shadow-[#087ab1]/40 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="sending"
                                    @click="emit('send')"
                                >
                                    <svg
                                        v-if="sending"
                                        class="h-4 w-4 animate-spin"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    <Send v-else class="h-4 w-4" />
                                    {{ sending ? 'Enviando...' : 'Enviar notificaciones' }}
                                </button>

                                <button
                                    v-else-if="(batch?.failed_count ?? 0) > 0 || (batch?.skipped_count ?? 0) > 0"
                                    type="button"
                                    class="flex items-center gap-2 rounded-xl border border-[#087ab1]/30 bg-[#087ab1]/5 px-5 py-2.5 text-sm font-semibold text-[#087ab1] transition-all hover:bg-[#087ab1]/10 disabled:opacity-60"
                                    :disabled="sending"
                                    @click="emit('send')"
                                >
                                    <RotateCcw class="h-4 w-4" />
                                    Reintentar fallidos
                                </button>
                            </div>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
