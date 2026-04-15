<script setup lang="ts">
import { useToastr } from '@/composables/useToastr';
import { useSwal } from '@/composables/useSwal';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    AlertTriangle,
    Bell,
    CalendarDays,
    CheckCircle2,
    Clock,
    Download,
    ExternalLink,
    FileSpreadsheet,
    Info,
    Lock,
    Mail,
    Pencil,
    Plus,
    RefreshCw,
    Save,
    Search,
    Trash2,
    Upload,
    UserPlus,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

useToastr();
const Swal = useSwal();

/* =========================
Period guard
========================= */
const page = usePage();
const currentPeriod = computed<{ id: number; name: string; status: string } | null>(
    () => (page.props as any).currentPeriod ?? null,
);
const periodIsActive = computed(() => currentPeriod.value?.status === 'active');

/* =========================
Types
========================= */
interface TeacherResult {
    id: number;
    dni: string;
    full_name: string;
    email: string | null;
    is_active: boolean;
}

interface SavedGroup {
    id: number;
    name: string;
    status: string;
    teachers_count: number;
    teachers: TeacherResult[];
    created_by: string;
    created_at: string;
}

interface PersonImport {
    id: number;
    file_name: string;
    file_size: number | null;
    total_rows: number;
    created_count: number;
    email_updated_count: number;
    skipped_count: number;
    ignored_count: number;
    imported_by: string;
    imported_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

/* =========================
Props
========================= */
const props = defineProps<{
    templateUrl: string;
    imports: {
        data: PersonImport[];
        links: PaginationLink[];
        total?: number;
    };
    groups: SavedGroup[];
}>();

/* =========================
Tabs
========================= */
const activeTab = ref<'import' | 'manual'>('import');

/* =========================
Importar tab
========================= */
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);

const importForm = useForm({
    file: null as File | null,
});

const onFileChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    selectedFile.value = input.files?.[0] ?? null;
    importForm.file = selectedFile.value;
};

const clearFile = () => {
    selectedFile.value = null;
    importForm.file = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submitImport = () => {
    if (!importForm.file) return;

    importForm.post(route('admin.persons.import'), {
        forceFormData: true,
        onSuccess: () => clearFile(),
    });
};

/* =========================
Manual tab — search
========================= */
const searchQuery = ref('');
const searchResults = ref<TeacherResult[]>([]);
const searchLoading = ref(false);
const searchNotFound = ref(false);
let searchTimer: ReturnType<typeof setTimeout> | null = null;

const runSearch = async () => {
    const q = searchQuery.value.trim();
    if (q.length < 2) {
        searchResults.value = [];
        searchNotFound.value = false;
        return;
    }
    searchLoading.value = true;
    try {
        const res = await axios.get(route('admin.teachers.search'), { params: { q } });
        searchResults.value = res.data.teachers ?? [];
        searchNotFound.value = searchResults.value.length === 0;
    } finally {
        searchLoading.value = false;
    }
};

const onSearchInput = () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchNotFound.value = false;
    if (searchQuery.value.trim().length < 2) {
        searchResults.value = [];
        return;
    }
    searchTimer = setTimeout(runSearch, 320);
};

const clearSearch = () => {
    searchQuery.value = '';
    searchResults.value = [];
    searchNotFound.value = false;
    quickForm.value.dni = '';
    quickForm.value.full_name = '';
    quickForm.value.email = '';
    showQuickForm.value = false;
};

/* =========================
Manual tab — quick create
========================= */
const showQuickForm = ref(false);
const quickSaving = ref(false);
const quickForm = ref({ dni: '', full_name: '', email: '' });
const quickErrors = ref<Record<string, string>>({});

const openQuickForm = () => {
    quickForm.value = {
        dni: /^\d+$/.test(searchQuery.value.trim()) ? searchQuery.value.trim() : '',
        full_name: /^\d+$/.test(searchQuery.value.trim()) ? '' : searchQuery.value.trim(),
        email: '',
    };
    quickErrors.value = {};
    showQuickForm.value = true;
};

const saveQuickTeacher = async () => {
    quickErrors.value = {};
    quickSaving.value = true;
    try {
        const res = await axios.post(route('admin.persons.quick-store'), quickForm.value);
        addToGroup(res.data.teacher);
        clearSearch();
    } catch (err: any) {
        if (err.response?.status === 422) {
            quickErrors.value = err.response.data.errors ?? {};
        }
    } finally {
        quickSaving.value = false;
    }
};

/* =========================
Manual tab — group builder
========================= */
const groupTeachers = ref<TeacherResult[]>([]);
const groupName = ref('');
const groupSaving = ref(false);
const editingGroupId = ref<number | null>(null);

const addToGroup = (teacher: TeacherResult) => {
    if (groupTeachers.value.some((t) => t.id === teacher.id)) return;
    groupTeachers.value.push(teacher);
    if (!groupName.value) {
        groupName.value = 'Grupo';
    }
    clearSearch();
};

const removeFromGroup = (idx: number) => {
    groupTeachers.value.splice(idx, 1);
};

const clearGroup = () => {
    groupTeachers.value = [];
    groupName.value = '';
    editingGroupId.value = null;
};

const editGroup = (group: SavedGroup) => {
    groupTeachers.value = [...group.teachers];
    groupName.value = group.name;
    editingGroupId.value = group.id;
    clearSearch();
    // Scroll to the builder
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const deleteGroup = (group: SavedGroup) => {
    Swal.fire({
        title: '¿Eliminar grupo?',
        html: `Se eliminará el grupo <strong>${group.name}</strong> y sus ${group.teachers_count} docente${group.teachers_count !== 1 ? 's' : ''} asociados. Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        focusCancel: true,
    }).then((result) => {
        if (!result.isConfirmed) return;
        router.delete(route('admin.persons.delete-group', group.id), { preserveScroll: true });
    });
};

const concretarGroup = (group: SavedGroup) => {
    Swal.fire({
        title: '¿Concretar este lote?',
        html: `El lote <strong>${group.name}</strong> pasará a estado <strong>Activo</strong> y ya no podrás editarlo desde aquí.<br><br>Podrás enviar las notificaciones desde <strong>Lotes de Notificación</strong>.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, concretar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#087ab1',
        cancelButtonColor: '#6b7280',
        focusCancel: true,
    }).then((result) => {
        if (!result.isConfirmed) return;
        router.post(route('admin.persons.concretar-group', group.id), {}, { preserveScroll: false });
    });
};

const saveGroup = () => {
    if (!groupTeachers.value.length || !groupName.value.trim() || groupSaving.value) return;
    groupSaving.value = true;

    const payload = { name: groupName.value, teacher_ids: groupTeachers.value.map((t) => t.id) };
    const options = {
        preserveScroll: true,
        onSuccess: () => clearGroup(),
        onFinish: () => { groupSaving.value = false; },
    };

    if (editingGroupId.value !== null) {
        router.patch(route('admin.persons.update-group', editingGroupId.value), payload, options);
    } else {
        router.post(route('admin.persons.group'), payload, options);
    }
};

/* =========================
Helpers
========================= */
const translateLabel = (label: string): string =>
    label.replace('Previous', 'Anterior').replace('Next', 'Siguiente');

const isPageNumber = (label: string): boolean =>
    /^\d+$/.test(label.replace(/&[^;]+;/g, '').trim());

const goToPage = (url: string | null) => {
    if (!url) return;
    router.visit(url, { preserveScroll: true, preserveState: true });
};

const formatFileSize = (bytes?: number | null): string => {
    if (!bytes) return '—';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const formatDate = (date: string): string =>
    new Date(date).toLocaleDateString('es-PE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

const groupStatusLabel: Record<string, string> = {
    draft: 'Borrador',
    active: 'Hecho',
    processing: 'Procesando',
    completed: 'Completado',
    completed_with_errors: 'Con errores',
    cancelled: 'Cancelado',
};

const groupStatusClass: Record<string, string> = {
    draft: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    processing: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    completed: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    completed_with_errors: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    cancelled: 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500',
};

/* =========================
Delete
========================= */
const deletingId = ref<number | null>(null);

const deleteImport = (item: PersonImport) => {
    deletingId.value = item.id;

    Swal.fire({
        title: '¿Eliminar registro?',
        html: `Se eliminará el historial de importación de <strong>${item.file_name}</strong>. Los docentes registrados con ese archivo <strong>no serán eliminados</strong>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        focusCancel: true,
    }).then((result) => {
        if (!result.isConfirmed) {
            deletingId.value = null;
            return;
        }

        router.delete(route('admin.persons.destroy', item.id), {
            preserveScroll: true,
            onFinish: () => (deletingId.value = null),
        });
    });
};
</script>

<template>
    <Head title="Difusión a Docentes" />

    <AppLayout>
        <div class="space-y-6 px-6 py-6">
            <!-- HEADER -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#087ab1] shadow-md"
                    >
                        <Users class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h1
                            class="text-xl font-bold text-gray-900 dark:text-gray-100"
                        >
                            Difusión a Docentes
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Registra o actualiza datos de contacto de docentes
                        </p>
                    </div>
                </div>

                <!-- Periodo badge -->
                <div
                    class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-sm font-medium"
                    :class="
                        periodIsActive
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400'
                            : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-400'
                    "
                >
                    <span class="relative flex h-2 w-2 shrink-0">
                        <span
                            v-if="periodIsActive"
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                        />
                        <span
                            class="relative inline-flex h-2 w-2 rounded-full"
                            :class="
                                periodIsActive
                                    ? 'bg-emerald-500'
                                    : 'bg-amber-400'
                            "
                        />
                    </span>
                    <CalendarDays class="h-3.5 w-3.5 shrink-0" />
                    <span>
                        {{
                            currentPeriod?.name ?? 'Sin periodo seleccionado'
                        }}
                    </span>
                    <span
                        class="ml-0.5 rounded-full px-1.5 py-0 text-xs font-semibold"
                        :class="
                            periodIsActive
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50'
                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50'
                        "
                    >
                        {{ periodIsActive ? 'Activo' : 'Histórico' }}
                    </span>
                </div>
            </div>

            <!-- BLOQUEO: periodo no activo -->
            <div
                v-if="!periodIsActive"
                class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-700/50 dark:bg-amber-900/15"
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/40"
                >
                    <Lock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p
                        class="text-sm font-semibold text-amber-800 dark:text-amber-300"
                    >
                        Acciones bloqueadas — periodo histórico
                    </p>
                    <p
                        class="mt-0.5 text-xs text-amber-700 dark:text-amber-400"
                    >
                        Estás consultando el periodo
                        <strong>{{ currentPeriod?.name }}</strong>, que no está
                        activo. Para importar personas o registrar manualmente,
                        cambia al periodo activo desde el selector en la barra
                        superior.
                    </p>
                </div>
            </div>

            <!-- CARD FORMULARIO -->
            <div
                v-if="periodIsActive"
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <!-- TABS -->
                <div
                    class="flex border-b border-gray-200 dark:border-gray-700"
                >
                    <button
                        type="button"
                        @click="activeTab = 'import'"
                        class="relative flex items-center gap-2 px-6 py-4 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'import'
                                ? 'border-b-2 border-[#087ab1] text-[#087ab1]'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                    >
                        <FileSpreadsheet class="h-4 w-4" />
                        Importar Docentes
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'manual'"
                        class="relative flex items-center gap-2 px-6 py-4 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'manual'
                                ? 'border-b-2 border-[#087ab1] text-[#087ab1]'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                    >
                        <UserPlus class="h-4 w-4" />
                        Manual
                    </button>
                </div>

                <!-- TAB: IMPORTAR -->
                <div v-if="activeTab === 'import'" class="space-y-6 p-6">
                    <!-- Reglas -->
                    <div
                        class="flex gap-3 rounded-xl border border-[#68c8fb]/30 bg-[#087ab1]/5 p-4"
                    >
                        <Info
                            class="mt-0.5 h-4 w-4 shrink-0 text-[#087ab1]"
                        />
                        <div
                            class="space-y-1 text-sm text-gray-700 dark:text-gray-300"
                        >
                            <p class="font-semibold text-[#087ab1]">
                                Lógica de importación
                            </p>
                            <ul
                                class="list-inside list-disc space-y-0.5 text-xs text-gray-600 dark:text-gray-400"
                            >
                                <li>
                                    Si el DNI
                                    <strong>no existe</strong> → se crea el
                                    registro.
                                </li>
                                <li>
                                    Si el DNI
                                    <strong>existe sin correo</strong> y el
                                    archivo trae uno → se actualiza el correo.
                                </li>
                                <li>
                                    Si el DNI
                                    <strong>existe con correo</strong> → se
                                    omite (sin cambios).
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Descargar plantilla -->
                    <div class="flex items-center gap-3">
                        <a
                            :href="props.templateUrl"
                            class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400 dark:hover:bg-emerald-900/40"
                        >
                            <Download class="h-4 w-4" />
                            Descargar plantilla Excel
                        </a>
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            Columnas: DNI, Nombre Completo, Email
                        </span>
                    </div>

                    <!-- Formulario carga -->
                    <form @submit.prevent="submitImport" class="space-y-4">
                        <label
                            class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center transition hover:border-[#087ab1] hover:bg-[#087ab1]/5 dark:border-gray-600 dark:bg-gray-800 dark:hover:border-[#68c8fb]"
                            :class="
                                selectedFile
                                    ? 'border-[#087ab1] bg-[#087ab1]/5'
                                    : ''
                            "
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".xlsx,.xls"
                                class="sr-only"
                                @change="onFileChange"
                            />
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-full"
                                :class="
                                    selectedFile
                                        ? 'bg-[#087ab1]/15'
                                        : 'bg-gray-200 dark:bg-gray-700'
                                "
                            >
                                <Upload
                                    class="h-5 w-5"
                                    :class="
                                        selectedFile
                                            ? 'text-[#087ab1]'
                                            : 'text-gray-400'
                                    "
                                />
                            </div>
                            <div>
                                <p
                                    class="text-sm font-medium"
                                    :class="
                                        selectedFile
                                            ? 'text-[#087ab1]'
                                            : 'text-gray-600 dark:text-gray-300'
                                    "
                                >
                                    {{
                                        selectedFile
                                            ? selectedFile.name
                                            : 'Haz clic para seleccionar un archivo'
                                    }}
                                </p>
                                <p
                                    v-if="!selectedFile"
                                    class="mt-1 text-xs text-gray-400"
                                >
                                    .xlsx o .xls — máximo 10 MB
                                </p>
                                <button
                                    v-if="selectedFile"
                                    type="button"
                                    @click.prevent="clearFile"
                                    class="mt-1 text-xs text-red-400 hover:text-red-600"
                                >
                                    Quitar archivo
                                </button>
                            </div>
                        </label>

                        <p
                            v-if="importForm.errors.file"
                            class="text-xs text-red-500"
                        >
                            {{ importForm.errors.file }}
                        </p>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="
                                    !selectedFile || importForm.processing
                                "
                                class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-5 py-2.5 text-sm font-medium text-white shadow-md transition hover:from-[#066a98] hover:to-[#4fbdf5] active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Upload class="h-4 w-4" />
                                {{
                                    importForm.processing
                                        ? 'Importando...'
                                        : 'Importar'
                                }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB: MANUAL -->
                <div
                    v-else-if="activeTab === 'manual'"
                    class="space-y-5 p-6"
                >
                    <!-- Instrucción -->
                    <div
                        class="flex gap-3 rounded-xl border border-[#68c8fb]/30 bg-[#087ab1]/5 p-4"
                    >
                        <Info class="mt-0.5 h-4 w-4 shrink-0 text-[#087ab1]" />
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Busca docentes por <strong>DNI, nombre o correo</strong>. Si el docente existe lo agrega al grupo directamente; si no existe te permite registrarlo al instante. Una vez armado el grupo, guárdalo como borrador para enviarlo desde
                            <strong>Lotes de Notificación</strong>.
                        </p>
                    </div>

                    <!-- Buscador -->
                    <div class="relative max-w-lg">
                        <div class="relative">
                            <Search class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                @input="onSearchInput"
                                type="text"
                                placeholder="Buscar por DNI, nombre o correo…"
                                class="w-full rounded-xl border border-gray-300 py-2.5 pr-9 pl-10 text-sm text-gray-900 transition focus:border-[#087ab1] focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <button
                                v-if="searchQuery"
                                type="button"
                                @click="clearSearch"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <!-- Spinner -->
                        <p v-if="searchLoading" class="mt-2 text-xs text-gray-400">
                            Buscando…
                        </p>

                        <!-- Resultados -->
                        <div
                            v-else-if="searchResults.length > 0"
                            class="mt-1 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                        >
                            <button
                                v-for="t in searchResults"
                                :key="t.id"
                                type="button"
                                @click="addToGroup(t)"
                                class="group flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-[#087ab1]/5 dark:hover:bg-[#087ab1]/10"
                                :class="groupTeachers.some(g => g.id === t.id) ? 'opacity-40 pointer-events-none' : ''"
                            >
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#087ab1]/10">
                                    <UserPlus class="h-3.5 w-3.5 text-[#087ab1]" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">
                                        {{ t.full_name }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        DNI: {{ t.dni }}
                                        <span v-if="t.email"> · {{ t.email }}</span>
                                        <span v-else class="text-amber-500"> · Sin correo</span>
                                    </p>
                                </div>
                                <Plus class="h-4 w-4 shrink-0 text-[#087ab1] opacity-0 transition group-hover:opacity-100" />
                            </button>
                        </div>

                        <!-- No encontrado -->
                        <div
                            v-else-if="searchNotFound && !showQuickForm"
                            class="mt-1 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-800"
                        >
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                No se encontró ningún docente con
                                <strong class="text-gray-700 dark:text-gray-200">"{{ searchQuery }}"</strong>.
                            </p>
                            <button
                                type="button"
                                @click="openQuickForm"
                                class="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-[#087ab1] hover:underline"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                Crear nuevo docente y agregar al grupo
                            </button>
                        </div>

                        <!-- Formulario rápido de creación -->
                        <div
                            v-if="showQuickForm"
                            class="mt-2 space-y-3 rounded-xl border border-[#68c8fb]/40 bg-[#087ab1]/5 p-4"
                        >
                            <p class="text-xs font-semibold text-[#087ab1]">Nuevo docente</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">DNI <span class="text-red-400">*</span></label>
                                    <input
                                        v-model="quickForm.dni"
                                        type="text"
                                        maxlength="20"
                                        placeholder="12345678"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#087ab1] focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                        :class="quickErrors.dni ? 'border-red-400' : ''"
                                    />
                                    <p v-if="quickErrors.dni" class="text-xs text-red-500">{{ quickErrors.dni[0] }}</p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Correo <span class="text-gray-400 text-xs">(opc.)</span></label>
                                    <input
                                        v-model="quickForm.email"
                                        type="email"
                                        maxlength="255"
                                        placeholder="correo@ejemplo.com"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#087ab1] focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                        :class="quickErrors.email ? 'border-red-400' : ''"
                                    />
                                    <p v-if="quickErrors.email" class="text-xs text-red-500">{{ quickErrors.email[0] }}</p>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Nombre completo <span class="text-red-400">*</span></label>
                                <input
                                    v-model="quickForm.full_name"
                                    type="text"
                                    maxlength="255"
                                    placeholder="Apellidos y nombres"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#087ab1] focus:ring-2 focus:ring-[#68c8fb]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                    :class="quickErrors.full_name ? 'border-red-400' : ''"
                                />
                                <p v-if="quickErrors.full_name" class="text-xs text-red-500">{{ quickErrors.full_name[0] }}</p>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="showQuickForm = false" class="rounded-lg px-3 py-1.5 text-xs text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    @click="saveQuickTeacher"
                                    :disabled="quickSaving"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-[#087ab1] px-3 py-1.5 text-xs font-medium text-white transition hover:bg-[#066a98] disabled:opacity-50"
                                >
                                    <Plus class="h-3 w-3" />
                                    {{ quickSaving ? 'Creando…' : 'Crear y agregar' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Card grupo en construcción -->
                    <transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div
                            v-if="groupTeachers.length > 0"
                            class="overflow-hidden rounded-2xl border shadow-md"
                            :class="editingGroupId ? 'border-violet-200 bg-white dark:border-violet-700/40 dark:bg-gray-900' : 'border-[#087ab1]/20 bg-white dark:border-[#087ab1]/30 dark:bg-gray-900'"
                        >
                            <!-- Edit mode banner -->
                            <div
                                v-if="editingGroupId"
                                class="flex items-center gap-2 border-b border-violet-100 bg-violet-50 px-5 py-2 text-xs font-medium text-violet-700 dark:border-violet-800/40 dark:bg-violet-900/20 dark:text-violet-400"
                            >
                                <RefreshCw class="h-3 w-3" />
                                Editando grupo existente —
                                <button type="button" @click="clearGroup" class="underline hover:no-underline">
                                    Cancelar edición
                                </button>
                            </div>

                            <!-- Card header -->
                            <div
                                class="flex items-center gap-3 border-b border-gray-100 px-5 py-3.5 dark:border-gray-700"
                                :class="editingGroupId ? 'bg-violet-50/50 dark:bg-violet-900/10' : 'bg-[#087ab1]/5 dark:bg-[#087ab1]/10'"
                            >
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                    :class="editingGroupId ? 'bg-violet-500' : 'bg-[#087ab1]'"
                                >
                                    <Users class="h-4 w-4 text-white" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                            {{ editingGroupId ? 'Editando grupo' : 'Nuevo grupo de notificación' }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                            Borrador
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ groupTeachers.length }} docente{{ groupTeachers.length !== 1 ? 's' : '' }} seleccionado{{ groupTeachers.length !== 1 ? 's' : '' }}
                                    </p>
                                </div>
                                <button
                                    v-if="editingGroupId"
                                    type="button"
                                    @click="deleteGroup(props.groups.find(g => g.id === editingGroupId)!)"
                                    title="Eliminar grupo"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-red-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    @click="clearGroup"
                                    title="Descartar"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <!-- Nombre del grupo -->
                            <div class="flex items-center gap-2 border-b border-gray-100 px-5 py-3 dark:border-gray-700">
                                <Pencil class="h-3.5 w-3.5 shrink-0 text-gray-400" />
                                <input
                                    v-model="groupName"
                                    type="text"
                                    maxlength="255"
                                    placeholder="Nombre del grupo…"
                                    class="flex-1 bg-transparent text-sm font-medium text-gray-700 placeholder-gray-400 focus:outline-none dark:text-gray-200"
                                />
                            </div>

                            <!-- Lista de docentes -->
                            <ul class="max-h-56 divide-y divide-gray-50 overflow-y-auto dark:divide-gray-800">
                                <li
                                    v-for="(t, idx) in groupTeachers"
                                    :key="t.id"
                                    class="flex items-center gap-3 px-5 py-2.5"
                                >
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#087ab1]/10 text-xs font-bold text-[#087ab1]">
                                        {{ idx + 1 }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">
                                            {{ t.full_name }}
                                        </p>
                                        <p class="flex items-center gap-1 text-xs text-gray-400">
                                            <span>{{ t.dni }}</span>
                                            <span v-if="t.email" class="flex items-center gap-0.5">
                                                <Mail class="h-2.5 w-2.5" /> {{ t.email }}
                                            </span>
                                            <span v-else class="text-amber-500">· Sin correo</span>
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeFromGroup(idx)"
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-gray-300 transition hover:bg-red-50 hover:text-red-400 dark:hover:bg-red-900/20"
                                    >
                                        <X class="h-3 w-3" />
                                    </button>
                                </li>
                            </ul>

                            <!-- Acciones -->
                            <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3.5 dark:border-gray-700">
                                <p class="text-xs text-gray-400">
                                    <template v-if="editingGroupId">
                                        Los cambios reemplazarán el grupo existente en
                                    </template>
                                    <template v-else>
                                        Se creará un lote borrador en
                                    </template>
                                    <span class="font-medium text-gray-600 dark:text-gray-300">Lotes de Notificación</span>
                                </p>
                                <button
                                    type="button"
                                    @click="saveGroup"
                                    :disabled="groupSaving || !groupName.trim()"
                                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium text-white shadow-md transition active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="editingGroupId ? 'bg-linear-to-r from-violet-600 to-violet-400 hover:from-violet-700 hover:to-violet-500' : 'bg-linear-to-r from-[#087ab1] to-[#68c8fb] hover:from-[#066a98] hover:to-[#4fbdf5]'"
                                >
                                    <RefreshCw v-if="editingGroupId" class="h-3.5 w-3.5" />
                                    <Save v-else class="h-3.5 w-3.5" />
                                    {{ groupSaving ? 'Guardando…' : (editingGroupId ? 'Actualizar grupo' : 'Guardar grupo') }}
                                </button>
                            </div>
                        </div>
                    </transition>

                    <!-- Grupos guardados -->
                    <div v-if="props.groups.length > 0" class="space-y-3 pt-2">
                        <div class="flex items-center gap-2">
                            <Bell class="h-4 w-4 text-violet-500" />
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Grupos guardados
                            </h3>
                            <span class="ml-auto rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">
                                {{ props.groups.length }}
                            </span>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="(group, index) in props.groups"
                                :key="group.id"
                                class="flex flex-col gap-3 rounded-xl border p-3.5 transition"
                                :class="
                                    editingGroupId === group.id
                                        ? 'border-violet-300 bg-violet-50 ring-2 ring-violet-200 dark:border-violet-600 dark:bg-violet-900/20 dark:ring-violet-800'
                                        : 'border-violet-100 bg-violet-50/60 dark:border-violet-800/40 dark:bg-violet-900/10'
                                "
                            >
                                <!-- Top row -->
                                <div class="flex items-start gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-900/40">
                                        <Bell class="h-3.5 w-3.5 text-violet-600 dark:text-violet-400" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">
                                                {{ group.name }}
                                            </p>
                                            <span class="inline-flex shrink-0 items-center rounded-full bg-violet-600 px-1.5 py-0 text-[10px] font-bold leading-4 text-white dark:bg-violet-500">
                                                #{{ props.groups.length - index }}
                                            </span>
                                        </div>
                                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                                <Users class="h-3 w-3" />
                                                {{ group.teachers_count }} docente{{ group.teachers_count !== 1 ? 's' : '' }}
                                            </span>
                                            <span class="text-gray-300 dark:text-gray-600">·</span>
                                            <span
                                                class="inline-flex items-center rounded-full px-1.5 py-0.5 text-xs font-medium"
                                                :class="groupStatusClass[group.status] ?? 'bg-gray-100 text-gray-500'"
                                            >
                                                <CheckCircle2 v-if="group.status === 'completed' || group.status === 'active'" class="mr-1 h-2.5 w-2.5" />
                                                <Clock v-else class="mr-1 h-2.5 w-2.5" />
                                                {{ groupStatusLabel[group.status] ?? group.status }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                            <span class="font-medium text-gray-500 dark:text-gray-400">{{ group.created_by }}</span>
                                            · {{ formatDate(group.created_at) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Action buttons — solo borrador -->
                                <div
                                    v-if="group.status === 'draft'"
                                    class="flex items-center gap-2 border-t border-violet-100 pt-2.5 dark:border-violet-800/30"
                                >
                                    <!-- Editar -->
                                    <button
                                        type="button"
                                        @click="editGroup(group)"
                                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-violet-200 bg-white px-3 py-1.5 text-xs font-medium text-violet-700 transition hover:bg-violet-50 dark:border-violet-700 dark:bg-gray-800 dark:text-violet-400 dark:hover:bg-violet-900/20"
                                        :class="editingGroupId === group.id ? 'ring-1 ring-violet-400' : ''"
                                    >
                                        <Pencil class="h-3 w-3" />
                                        {{ editingGroupId === group.id ? 'Editando…' : 'Editar' }}
                                    </button>

                                    <!-- Concretar -->
                                    <button
                                        type="button"
                                        @click="concretarGroup(group)"
                                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-linear-to-r from-[#087ab1] to-[#68c8fb] px-3 py-1.5 text-xs font-medium text-white transition hover:from-[#066a98] hover:to-[#4fbdf5] active:scale-95"
                                    >
                                        <ExternalLink class="h-3 w-3" />
                                        Concretar lote
                                    </button>


                                </div>

                                <!-- Estado concretado (no-borrador) -->
                                <div
                                    v-else
                                    class="flex items-center gap-2 border-t border-violet-100 pt-2.5 dark:border-violet-800/30"
                                >
                                    <CheckCircle2 class="h-3.5 w-3.5 shrink-0 text-emerald-500" />
                                    <p class="flex-1 text-xs text-gray-500 dark:text-gray-400">
                                        Lote concretado — gestiona el envío desde
                                        <a
                                            :href="route('admin.notification-batches.index')"
                                            class="font-medium text-[#087ab1] hover:underline dark:text-[#68c8fb]"
                                        >
                                            Lotes de Notificación
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD FORMULARIO BLOQUEADO -->
            <div
                v-else
                class="flex items-center justify-center gap-3 rounded-2xl border border-dashed border-amber-200 bg-amber-50/50 px-6 py-10 text-center dark:border-amber-700/40 dark:bg-amber-900/10"
            >
                <AlertTriangle class="h-5 w-5 shrink-0 text-amber-400" />
                <p class="text-sm text-amber-700 dark:text-amber-400">
                    El formulario de importación está disponible solo en el
                    periodo activo.
                </p>
            </div>

            <!-- HISTORIAL DE IMPORTACIONES (solo tab Importar) -->
            <div
                v-if="activeTab === 'import'"
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <!-- Header tabla -->
                <div
                    class="flex items-center gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700"
                >
                    <FileSpreadsheet class="h-4 w-4 text-[#087ab1]" />
                    <h2
                        class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Historial de importaciones
                    </h2>
                    <span
                        class="ml-auto text-xs text-gray-400 dark:text-gray-500"
                    >
                        {{ imports.total ?? imports.data.length }} registro{{
                            (imports.total ?? imports.data.length) !== 1
                                ? 's'
                                : ''
                        }}
                    </span>
                </div>

                <table
                    class="min-w-full divide-y divide-gray-100 dark:divide-gray-700"
                >
                    <thead>
                        <tr
                            class="bg-linear-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800"
                        >
                            <th
                                class="px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                            >
                                Archivo
                            </th>
                            <th
                                class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase sm:table-cell dark:text-gray-400"
                            >
                                Importado por
                            </th>
                            <th
                                class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase md:table-cell dark:text-gray-400"
                            >
                                Tamaño
                            </th>
                            <th
                                class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase lg:table-cell dark:text-gray-400"
                            >
                                Filas
                            </th>
                            <th
                                class="hidden px-5 py-3.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase sm:table-cell dark:text-gray-400"
                            >
                                Fecha
                            </th>
                            <th
                                class="px-5 py-3.5 text-right text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                            >
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-gray-100 dark:divide-gray-700/60"
                    >
                        <tr
                            v-for="item in imports.data"
                            :key="item.id"
                            class="group transition-all duration-150 hover:bg-[#68c8fb]/2 hover:shadow-[inset_3px_0_0_#087ab1] dark:hover:bg-[#68c8fb]/10 dark:hover:shadow-[inset_3px_0_0_#68c8fb]"
                        >
                            <!-- Archivo -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#087ab1]/10"
                                    >
                                        <FileSpreadsheet
                                            class="h-4 w-4 text-[#087ab1]"
                                        />
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="max-w-[200px] truncate text-sm font-medium text-gray-800 dark:text-gray-100"
                                        >
                                            {{ item.file_name }}
                                        </p>
                                        <!-- Resumen inline en móvil -->
                                        <p
                                            class="text-xs text-gray-400 sm:hidden"
                                        >
                                            {{ item.imported_by }} ·
                                            {{ formatDate(item.imported_at) }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Importado por -->
                            <td class="hidden px-5 py-3.5 sm:table-cell">
                                <span
                                    class="text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ item.imported_by }}
                                </span>
                            </td>

                            <!-- Tamaño -->
                            <td class="hidden px-5 py-3.5 md:table-cell">
                                <span
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{ formatFileSize(item.file_size) }}
                                </span>
                            </td>

                            <!-- Filas -->
                            <td class="hidden px-5 py-3.5 lg:table-cell">
                                <div class="flex flex-col gap-0.5">
                                    <span
                                        class="inline-flex w-fit items-center rounded-full bg-[#087ab1]/10 px-2.5 py-0.5 text-xs font-medium text-[#087ab1] dark:bg-[#087ab1]/20 dark:text-[#68c8fb]"
                                    >
                                        {{ item.total_rows }} fila{{
                                            item.total_rows !== 1 ? 's' : ''
                                        }}
                                    </span>
                                    <span
                                        v-if="
                                            item.created_count > 0 ||
                                            item.email_updated_count > 0
                                        "
                                        class="text-xs text-gray-400"
                                    >
                                        <span
                                            v-if="item.created_count > 0"
                                            class="text-emerald-600 dark:text-emerald-400"
                                            >+{{ item.created_count }}
                                            nuevo{{
                                                item.created_count !== 1
                                                    ? 's'
                                                    : ''
                                            }}</span
                                        >
                                        <span
                                            v-if="
                                                item.created_count > 0 &&
                                                item.email_updated_count > 0
                                            "
                                            class="mx-1 text-gray-300"
                                            >·</span
                                        >
                                        <span
                                            v-if="
                                                item.email_updated_count > 0
                                            "
                                            class="text-amber-600 dark:text-amber-400"
                                            >{{ item.email_updated_count }}
                                            correo{{
                                                item.email_updated_count !== 1
                                                    ? 's'
                                                    : ''
                                            }}
                                            act.</span
                                        >
                                    </span>
                                </div>
                            </td>

                            <!-- Fecha -->
                            <td class="hidden px-5 py-3.5 sm:table-cell">
                                <span
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{ formatDate(item.imported_at) }}
                                </span>
                            </td>

                            <!-- Acciones -->
                            <td class="px-5 py-3.5 text-right">
                                <button
                                    @click="deleteImport(item)"
                                    title="Eliminar registro"
                                    class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full transition-all duration-200"
                                    :class="
                                        deletingId === item.id
                                            ? 'bg-red-600 text-white shadow-md'
                                            : 'text-red-500 hover:bg-red-600 hover:text-white'
                                    "
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </td>
                        </tr>

                        <!-- Sin datos -->
                        <tr v-if="imports.data.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div
                                        class="flex h-14 w-14 items-center justify-center rounded-full bg-[#087ab1]/10 dark:bg-[#087ab1]/20"
                                    >
                                        <FileSpreadsheet
                                            class="h-7 w-7 text-[#087ab1]/60"
                                        />
                                    </div>
                                    <p
                                        class="text-sm font-medium text-gray-500"
                                    >
                                        No hay importaciones registradas aún
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- PAGINACIÓN -->
                <div
                    v-if="imports.links && imports.links.length > 3"
                    class="flex justify-end px-5 py-4"
                >
                    <nav class="inline-flex gap-1">
                        <template
                            v-for="link in imports.links"
                            :key="link.label"
                        >
                            <a
                                v-if="link.url"
                                :href="link.url"
                                @click.prevent="goToPage(link.url)"
                                class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-all"
                                :class="
                                    link.active && isPageNumber(link.label)
                                        ? 'border-[#68c8fb] bg-[#68c8fb] text-white shadow-sm'
                                        : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'
                                "
                                v-html="translateLabel(link.label)"
                            />
                            <span
                                v-else
                                class="cursor-not-allowed rounded-lg border border-gray-100 bg-white px-3 py-1.5 text-sm font-medium text-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-600"
                                v-html="translateLabel(link.label)"
                            />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
