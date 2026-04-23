<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useSwal } from '@/composables/useSwal';
import type { BreadcrumbItem } from '@/types';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    BookOpen,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Clock,
    HeartHandshake,
    Info,
    Mail,
    MessageCircle,
    Pencil,
    Phone,
    Plus,
    Shield,
    Sparkles,
    Star,
    Tag,
    Trash2,
    UserPlus,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

/* ── Types ── */
interface Contact {
    id: number;
    name: string;
    role: string;
    department: string;
    email: string;
    phone: string;
    whatsapp: string | null;
    schedule: string | null;
    specialties: string[] | null;
    is_available: boolean;
    is_primary: boolean;
    sort_order: number;
}
interface SystemInfo {
    name: string;
    version: string;
    description: string;
    support_email: string;
}
interface Faq {
    question: string;
    answer: string;
}

const props = defineProps<{
    contacts: Contact[];
    systemInfo: SystemInfo;
    faqs: Faq[];
    canManage: boolean;
}>();

/* ── Breadcrumbs ── */
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Ayuda & Soporte', href: '/help' }];

/* ── FAQ accordion ── */
const openFaq = ref<number | null>(null);
const toggleFaq = (i: number) => {
    openFaq.value = openFaq.value === i ? null : i;
};

/* ── Helpers ── */
const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

const specialtyColors: Record<string, string> = {
    Servidores: '#0ea5e9',
    Redes: '#8b5cf6',
    'Bases de datos': '#f59e0b',
    'Soporte de software': '#10b981',
    Usuarios: '#68c8fb',
    'Correo institucional': '#f97316',
    Desarrollo: '#3b82f6',
    Integraciones: '#ec4899',
    APIs: '#06b6d4',
};
const getSpecialtyColor = (s: string) => specialtyColors[s] ?? '#94a3b8';

/* ── Modal ── */
const showModal = ref(false);
const editingContact = ref<Contact | null>(null);
const isEdit = computed(() => !!editingContact.value);

const form = useForm({
    name: '',
    role: '',
    department: '',
    email: '',
    phone: '',
    whatsapp: '',
    schedule: '',
    specialties: [] as string[],
    is_available: true,
    is_primary: false,
});

/* Specialty tag input */
const specialtyInput = ref('');
const addSpecialty = () => {
    const value = specialtyInput.value.trim().replace(/,$/, '');
    if (value && !form.specialties.includes(value)) {
        form.specialties.push(value);
    }
    specialtyInput.value = '';
};
const removeSpecialty = (index: number) => {
    form.specialties.splice(index, 1);
};
const onSpecialtyKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addSpecialty();
    } else if (e.key === 'Backspace' && specialtyInput.value === '' && form.specialties.length > 0) {
        form.specialties.pop();
    }
};

const openCreate = () => {
    editingContact.value = null;
    form.reset();
    form.clearErrors();
    form.specialties = [];
    specialtyInput.value = '';
    showModal.value = true;
};

const openEdit = (contact: Contact) => {
    editingContact.value = contact;
    form.name = contact.name;
    form.role = contact.role;
    form.department = contact.department;
    form.email = contact.email;
    form.phone = contact.phone;
    form.whatsapp = contact.whatsapp ?? '';
    form.schedule = contact.schedule ?? '';
    form.specialties = contact.specialties ? [...contact.specialties] : [];
    form.is_available = contact.is_available;
    form.is_primary = contact.is_primary;
    form.clearErrors();
    specialtyInput.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

watch(showModal, (val) => {
    if (!val) {
        editingContact.value = null;
        form.reset();
        form.clearErrors();
    }
});

const submit = () => {
    if (isEdit.value && editingContact.value) {
        form.put(route('admin.it-contacts.update', editingContact.value.id), {
            onSuccess: closeModal,
        });
    } else {
        form.post(route('admin.it-contacts.store'), {
            onSuccess: closeModal,
        });
    }
};

/* ── Delete ── */
const swal = useSwal();
const deleteContact = (contact: Contact) => {
    swal.fire({
        title: '¿Eliminar responsable TI?',
        text: `Se eliminará a "${contact.name}" de forma permanente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            useForm({}).delete(route('admin.it-contacts.destroy', contact.id));
        }
    });
};

/* ── Styles ── */
const inputClass =
    'w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-[#087ab1] focus:bg-white focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700/60 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-[#68c8fb] dark:focus:bg-gray-700';

const inputErrorClass =
    'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100 dark:border-red-500/60 dark:bg-red-900/10 dark:focus:border-red-500';
</script>

<template>
    <Head title="Ayuda & Soporte" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50/60 p-6 dark:bg-background">
            <div class="mx-auto max-w-6xl space-y-8">

                <!-- ══════════════════════════════════════
                     HERO
                ═══════════════════════════════════════ -->
                <div
                    class="relative overflow-hidden rounded-2xl p-8 text-white shadow-lg"
                    style="background: linear-gradient(135deg, #032f4a 0%, #04395a 50%, #065c8e 100%);"
                >
                    <div class="pointer-events-none absolute inset-0">
                        <div
                            class="absolute -top-20 -right-20 h-64 w-64 rounded-full opacity-10"
                            style="background: radial-gradient(circle, #68c8fb, transparent);"
                        />
                        <div
                            class="absolute -bottom-12 -left-12 h-48 w-48 rounded-full opacity-10"
                            style="background: radial-gradient(circle, #68c8fb, transparent);"
                        />
                        <div
                            class="absolute inset-0 opacity-[0.03]"
                            style="background-image: radial-gradient(circle, #68c8fb 1px, transparent 1px); background-size: 28px 28px;"
                        />
                    </div>

                    <div class="relative flex flex-wrap items-center justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl shadow-lg"
                                style="background: rgba(104,200,251,0.18); border: 1px solid rgba(104,200,251,0.3);"
                            >
                                <HeartHandshake class="h-7 w-7" style="color: #68c8fb;" />
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-white">Centro de Ayuda & Soporte</h1>
                                <p class="mt-0.5 text-sm" style="color: rgba(255,255,255,0.6);">
                                    Equipo de Tecnologías de la Información — {{ systemInfo.name }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2 rounded-xl px-4 py-2.5"
                            style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);"
                        >
                            <Sparkles class="h-4 w-4" style="color: #68c8fb;" />
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-wider" style="color: rgba(255,255,255,0.5);">
                                    Versión del sistema
                                </p>
                                <p class="text-sm font-semibold text-white">v{{ systemInfo.version }}</p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="relative mt-6 rounded-xl p-4"
                        style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);"
                    >
                        <div class="flex items-start gap-3">
                            <Info class="mt-0.5 h-4 w-4 shrink-0" style="color: #68c8fb;" />
                            <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.75);">
                                {{ systemInfo.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════
                     CONTACTOS TI
                ═══════════════════════════════════════ -->
                <div>
                    <div class="mb-4 flex items-center gap-3">
                        <Shield class="h-5 w-5" style="color: #04395a;" />
                        <h2 class="text-lg font-bold text-foreground dark:text-white">Responsables de TI</h2>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            style="background: rgba(4,57,90,0.08); color: #04395a;"
                        >
                            {{ contacts.length }} contactos
                        </span>

                        <button
                            v-if="canManage"
                            type="button"
                            @click="openCreate"
                            class="ml-auto flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-md transition-all hover:opacity-90 hover:shadow-lg"
                            style="background: linear-gradient(135deg, #04395a, #068ab8);"
                        >
                            <Plus class="h-4 w-4" />
                            Nuevo Responsable TI
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="contact in contacts"
                            :key="contact.id"
                            class="group relative overflow-hidden rounded-2xl border bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:bg-card"
                            :style="contact.is_primary
                                ? 'border-color: rgba(104,200,251,0.5);'
                                : 'border-color: rgba(0,0,0,0.07);'"
                        >
                            <!-- Acento primario -->
                            <div
                                v-if="contact.is_primary"
                                class="absolute inset-x-0 top-0 h-0.5"
                                style="background: linear-gradient(90deg, #04395a, #68c8fb, #04395a);"
                            />

                            <!-- Fondo hover -->
                            <div
                                class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                style="background: radial-gradient(ellipse at top right, rgba(104,200,251,0.04), transparent 70%);"
                            />

                            <div class="relative p-5">
                                <!-- Header -->
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="relative shrink-0">
                                            <div
                                                class="flex h-12 w-12 items-center justify-center rounded-xl text-sm font-bold text-white shadow-md"
                                                :style="contact.is_primary
                                                    ? 'background: linear-gradient(135deg, #04395a, #068ab8);'
                                                    : 'background: linear-gradient(135deg, #475569, #64748b);'"
                                            >
                                                {{ getInitials(contact.name) }}
                                            </div>
                                            <div
                                                class="absolute -right-0.5 -bottom-0.5 h-3 w-3 rounded-full border-2 border-white dark:border-card"
                                                :class="contact.is_available ? 'bg-emerald-400' : 'bg-slate-400'"
                                            />
                                        </div>

                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <p class="truncate text-sm font-semibold text-foreground">
                                                    {{ contact.name }}
                                                </p>
                                                <Star
                                                    v-if="contact.is_primary"
                                                    class="h-3.5 w-3.5 shrink-0 fill-amber-400 text-amber-400"
                                                />
                                            </div>
                                            <p class="mt-0.5 truncate text-xs text-muted-foreground">
                                                {{ contact.role }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Disponibilidad + acciones admin -->
                                    <div class="flex shrink-0 flex-col items-end gap-2">
                                        <span
                                            class="flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-semibold"
                                            :class="contact.is_available
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400'
                                                : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'"
                                        >
                                            <CheckCircle2 v-if="contact.is_available" class="h-3 w-3" />
                                            <XCircle v-else class="h-3 w-3" />
                                            {{ contact.is_available ? 'Disponible' : 'Ocupado' }}
                                        </span>

                                        <!-- Botones admin -->
                                        <div v-if="canManage" class="flex gap-1">
                                            <button
                                                type="button"
                                                @click.stop="openEdit(contact)"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 transition-all hover:border-[#068ab8]/30 hover:bg-[#068ab8]/5 hover:text-[#068ab8] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-[#68c8fb]"
                                                title="Editar"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                type="button"
                                                @click.stop="deleteContact(contact)"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 transition-all hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                                                title="Eliminar"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Especialidades -->
                                <div v-if="contact.specialties && contact.specialties.length" class="mt-4 flex flex-wrap gap-1.5">
                                    <span
                                        v-for="s in contact.specialties"
                                        :key="s"
                                        class="rounded-md px-2 py-0.5 text-[11px] font-medium"
                                        :style="`background: ${getSpecialtyColor(s)}18; color: ${getSpecialtyColor(s)}; border: 1px solid ${getSpecialtyColor(s)}30;`"
                                    >
                                        {{ s }}
                                    </span>
                                </div>

                                <div class="my-4 h-px bg-border/60" />

                                <!-- Datos de contacto -->
                                <div class="space-y-2.5">
                                    <a
                                        :href="`mailto:${contact.email}`"
                                        class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs text-muted-foreground transition-colors hover:bg-muted/60 hover:text-foreground"
                                    >
                                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md" style="background: rgba(104,200,251,0.1);">
                                            <Mail class="h-3.5 w-3.5" style="color: #068ab8;" />
                                        </div>
                                        <span class="truncate">{{ contact.email }}</span>
                                    </a>

                                    <a
                                        :href="`tel:${contact.phone}`"
                                        class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs text-muted-foreground transition-colors hover:bg-muted/60 hover:text-foreground"
                                    >
                                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md" style="background: rgba(16,185,129,0.1);">
                                            <Phone class="h-3.5 w-3.5" style="color: #10b981;" />
                                        </div>
                                        <span class="truncate">{{ contact.phone }}</span>
                                    </a>

                                    <a
                                        v-if="contact.whatsapp"
                                        :href="`https://wa.me/${contact.whatsapp?.replace(/\D/g, '')}`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs text-muted-foreground transition-colors hover:bg-muted/60 hover:text-foreground"
                                    >
                                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md" style="background: rgba(37,211,102,0.1);">
                                            <MessageCircle class="h-3.5 w-3.5" style="color: #25d366;" />
                                        </div>
                                        <span>Escribir por WhatsApp</span>
                                    </a>

                                    <div
                                        v-if="contact.schedule"
                                        class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs text-muted-foreground"
                                    >
                                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md" style="background: rgba(245,158,11,0.1);">
                                            <Clock class="h-3.5 w-3.5" style="color: #f59e0b;" />
                                        </div>
                                        <span>{{ contact.schedule }}</span>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="contact.is_primary"
                                class="border-t px-5 py-2.5"
                                style="border-color: rgba(104,200,251,0.2); background: rgba(104,200,251,0.04);"
                            >
                                <p class="text-[10px] font-semibold uppercase tracking-wider" style="color: rgba(104,200,251,0.7);">
                                    Contacto principal
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════
                     FAQ
                ═══════════════════════════════════════ -->
                <div>
                    <div class="mb-4 flex items-center gap-2">
                        <BookOpen class="h-5 w-5" style="color: #04395a;" />
                        <h2 class="text-lg font-bold text-foreground dark:text-white">Preguntas frecuentes</h2>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(faq, i) in faqs"
                            :key="i"
                            class="overflow-hidden rounded-2xl border bg-white shadow-sm transition-all duration-200 dark:bg-card"
                            :style="openFaq === i ? 'border-color: rgba(104,200,251,0.4);' : 'border-color: rgba(0,0,0,0.07);'"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-muted/40"
                                @click="toggleFaq(i)"
                            >
                                <span class="text-sm font-medium text-foreground">{{ faq.question }}</span>
                                <div
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition-colors"
                                    :style="openFaq === i
                                        ? 'background: rgba(104,200,251,0.15); color: #04395a;'
                                        : 'background: rgba(0,0,0,0.04); color: #94a3b8;'"
                                >
                                    <ChevronUp v-if="openFaq === i" class="h-4 w-4" />
                                    <ChevronDown v-else class="h-4 w-4" />
                                </div>
                            </button>

                            <Transition
                                enter-active-class="transition-all duration-200 ease-out"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-150 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-1"
                            >
                                <div
                                    v-if="openFaq === i"
                                    class="border-t px-5 py-4"
                                    style="border-color: rgba(104,200,251,0.15); background: rgba(104,200,251,0.025);"
                                >
                                    <p class="text-sm leading-relaxed text-muted-foreground">{{ faq.answer }}</p>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════
                     CORREO SOPORTE GENERAL
                ═══════════════════════════════════════ -->
                <div
                    class="relative overflow-hidden rounded-2xl p-6"
                    style="background: linear-gradient(135deg, rgba(4,57,90,0.05), rgba(104,200,251,0.06)); border: 1px solid rgba(104,200,251,0.2);"
                >
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl"
                                style="background: rgba(104,200,251,0.12); border: 1px solid rgba(104,200,251,0.2);"
                            >
                                <Mail class="h-6 w-6" style="color: #04395a;" />
                            </div>
                            <div>
                                <p class="font-semibold text-foreground">¿Necesitas más ayuda?</p>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    Escríbenos directamente al correo de soporte general de TI.
                                </p>
                            </div>
                        </div>
                        <a
                            :href="`mailto:${systemInfo.support_email}`"
                            class="flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all hover:shadow-lg hover:brightness-110"
                            style="background: linear-gradient(135deg, #04395a, #068ab8);"
                        >
                            <Mail class="h-4 w-4" />
                            {{ systemInfo.support_email }}
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <!-- ══════════════════════════════════════
             MODAL CREAR / EDITAR CONTACTO TI
        ═══════════════════════════════════════ -->
        <TransitionRoot appear :show="showModal" as="template">
            <Dialog @close="closeModal" class="relative z-50">
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
                        class="relative w-full max-w-2xl"
                    >
                        <DialogPanel class="w-full overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-gray-900 dark:ring-white/10">
                            <!-- HEADER -->
                            <div class="relative overflow-hidden px-6 py-5" style="background: linear-gradient(135deg, #087ab1, #68c8fb);">
                                <div class="pointer-events-none absolute -top-12 -right-12 h-52 w-52 rounded-full bg-white/8" />
                                <div class="pointer-events-none absolute right-40 -bottom-8 h-36 w-36 rounded-full bg-white/5" />
                                <div class="relative flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                                        <UserPlus class="h-5 w-5 text-white" />
                                    </div>
                                    <div>
                                        <DialogTitle class="text-base font-bold text-white">
                                            {{ isEdit ? 'Editar Responsable TI' : 'Nuevo Responsable TI' }}
                                        </DialogTitle>
                                        <p class="text-xs text-[#d0eeff]">
                                            {{ isEdit ? 'Modifica los datos del contacto' : 'Completa los campos para registrar el responsable' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- BODY -->
                            <div class="max-h-[60vh] overflow-y-auto">
                                <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2">
                                    <!-- Nombre -->
                                    <div class="space-y-1.5">
                                        <label class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Nombre completo <span class="ml-auto font-normal normal-case text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            placeholder="Ej: Carlos Mamani Flores"
                                            :class="[inputClass, form.errors.name ? inputErrorClass : '']"
                                        />
                                        <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                                    </div>

                                    <!-- Cargo -->
                                    <div class="space-y-1.5">
                                        <label class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Cargo <span class="ml-auto font-normal normal-case text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="form.role"
                                            type="text"
                                            placeholder="Ej: Administrador de Sistemas"
                                            :class="[inputClass, form.errors.role ? inputErrorClass : '']"
                                        />
                                        <p v-if="form.errors.role" class="text-xs text-red-500">{{ form.errors.role }}</p>
                                    </div>

                                    <!-- Departamento -->
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <label class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Departamento <span class="ml-auto font-normal normal-case text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="form.department"
                                            type="text"
                                            placeholder="Ej: Tecnologías de la Información"
                                            :class="[inputClass, form.errors.department ? inputErrorClass : '']"
                                        />
                                        <p v-if="form.errors.department" class="text-xs text-red-500">{{ form.errors.department }}</p>
                                    </div>

                                    <!-- Email -->
                                    <div class="space-y-1.5">
                                        <label class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Correo electrónico <span class="ml-auto font-normal normal-case text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            placeholder="correo@upeu.edu.pe"
                                            :class="[inputClass, form.errors.email ? inputErrorClass : '']"
                                        />
                                        <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
                                    </div>

                                    <!-- Teléfono -->
                                    <div class="space-y-1.5">
                                        <label class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Teléfono <span class="ml-auto font-normal normal-case text-red-400">*</span>
                                        </label>
                                        <input
                                            v-model="form.phone"
                                            type="text"
                                            placeholder="+51 (051) 363-000 Ext. 3001"
                                            :class="[inputClass, form.errors.phone ? inputErrorClass : '']"
                                        />
                                        <p v-if="form.errors.phone" class="text-xs text-red-500">{{ form.errors.phone }}</p>
                                    </div>

                                    <!-- WhatsApp -->
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            WhatsApp <span class="ml-1 font-normal normal-case text-gray-400">(opcional)</span>
                                        </label>
                                        <input
                                            v-model="form.whatsapp"
                                            type="text"
                                            placeholder="Ej: 51987654321"
                                            :class="[inputClass, form.errors.whatsapp ? inputErrorClass : '']"
                                        />
                                        <p v-if="form.errors.whatsapp" class="text-xs text-red-500">{{ form.errors.whatsapp }}</p>
                                    </div>

                                    <!-- Horario -->
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Horario <span class="ml-1 font-normal normal-case text-gray-400">(opcional)</span>
                                        </label>
                                        <input
                                            v-model="form.schedule"
                                            type="text"
                                            placeholder="Ej: Lun – Vie: 8:00 am – 5:00 pm"
                                            :class="[inputClass, form.errors.schedule ? inputErrorClass : '']"
                                        />
                                        <p v-if="form.errors.schedule" class="text-xs text-red-500">{{ form.errors.schedule }}</p>
                                    </div>

                                    <!-- Especialidades tag input -->
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <label class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            <Tag class="h-3.5 w-3.5 text-[#68c8fb]" />
                                            Especialidades
                                            <span class="ml-auto font-normal normal-case text-gray-400">(Enter o coma para agregar)</span>
                                        </label>
                                        <div
                                            class="flex min-h-[42px] flex-wrap gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 transition-all focus-within:border-[#087ab1] focus-within:bg-white focus-within:ring-2 focus-within:ring-[#68c8fb]/20 dark:border-gray-600 dark:bg-gray-700/60 dark:focus-within:border-[#68c8fb] dark:focus-within:bg-gray-700"
                                        >
                                            <span
                                                v-for="(sp, si) in form.specialties"
                                                :key="si"
                                                class="flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium"
                                                :style="`background: ${getSpecialtyColor(sp)}18; color: ${getSpecialtyColor(sp)}; border: 1px solid ${getSpecialtyColor(sp)}30;`"
                                            >
                                                {{ sp }}
                                                <button
                                                    type="button"
                                                    @click="removeSpecialty(si)"
                                                    class="ml-0.5 opacity-60 transition-opacity hover:opacity-100"
                                                >
                                                    <X class="h-3 w-3" />
                                                </button>
                                            </span>
                                            <input
                                                v-model="specialtyInput"
                                                type="text"
                                                placeholder="Ej: Servidores, Redes..."
                                                class="min-w-[120px] flex-1 bg-transparent text-sm text-gray-900 placeholder-gray-400 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                                                @keydown="onSpecialtyKeydown"
                                                @blur="addSpecialty"
                                            />
                                        </div>
                                    </div>

                                    <!-- Toggles -->
                                    <div class="space-y-3 sm:col-span-2">
                                        <!-- Disponible -->
                                        <label class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 transition-colors hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700/40 dark:hover:bg-gray-700">
                                            <div>
                                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Disponible actualmente</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Indica si este responsable está disponible para atender</p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="form.is_available = !form.is_available"
                                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus:outline-none"
                                                :class="form.is_available ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600'"
                                            >
                                                <span
                                                    class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transition-transform"
                                                    :class="form.is_available ? 'translate-x-5' : 'translate-x-0.5'"
                                                />
                                            </button>
                                        </label>

                                        <!-- Primario -->
                                        <label class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 transition-colors hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700/40 dark:hover:bg-gray-700">
                                            <div>
                                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Contacto principal</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Se destacará como referente principal del equipo TI</p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="form.is_primary = !form.is_primary"
                                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus:outline-none"
                                                :class="form.is_primary ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-600'"
                                            >
                                                <span
                                                    class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transition-transform"
                                                    :class="form.is_primary ? 'translate-x-5' : 'translate-x-0.5'"
                                                />
                                            </button>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- FOOTER -->
                            <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50/70 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/50">
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    <span class="text-red-400">*</span> Campos requeridos
                                </p>
                                <div class="flex gap-3">
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="button"
                                        @click="submit"
                                        :disabled="form.processing"
                                        class="flex items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#087ab1]/30 transition-all hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                        {{ form.processing ? 'Guardando...' : isEdit ? 'Actualizar' : 'Guardar' }}
                                    </button>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>
