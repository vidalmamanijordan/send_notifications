<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { AlertTriangle, CheckCircle2, Hash, Loader2, Mail, Search, UserRound, X, XCircle } from 'lucide-vue-next'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { route } from 'ziggy-js'

/* ── Types ── */
interface Teacher {
    id: number
    dni: string
    full_name: string
    email: string | null
    is_active: boolean
    expired_in_period: number
    records_in_period: number
}

interface Period {
    id: number
    name: string
}

interface SearchResponse {
    period: Period | null
    teachers: Teacher[]
}

/* ── State ── */
const query        = ref('')
const teachers     = ref<Teacher[]>([])
const period       = ref<Period | null>(null)
const isLoading    = ref(false)
const isOpen       = ref(false)
const activeIndex  = ref(-1)
const hasSearched  = ref(false)
const inputRef     = ref<HTMLInputElement | null>(null)
const containerRef = ref<HTMLDivElement | null>(null)
let debounceTimer: ReturnType<typeof setTimeout>

const showDropdown = computed(() => isOpen.value && query.value.trim().length >= 2)

/* ── Helpers ── */
function escapeRegex(str: string) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

function highlight(text: string): string {
    if (!query.value.trim()) return text
    const escaped = escapeRegex(query.value.trim())
    return text.replace(new RegExp(`(${escaped})`, 'gi'), '<mark>$1</mark>')
}

function getInitials(name: string): string {
    return name.split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase()
}

function avatarColor(id: number): string {
    const colors = [
        'bg-blue-500', 'bg-violet-500', 'bg-emerald-500',
        'bg-rose-500', 'bg-amber-500', 'bg-cyan-500',
        'bg-indigo-500', 'bg-pink-500',
    ]
    return colors[id % colors.length]
}

/* ── Fetch ── */
async function performSearch() {
    const q = query.value.trim()
    if (q.length < 2) {
        teachers.value = []
        period.value   = null
        isOpen.value   = false
        hasSearched.value = false
        return
    }

    isLoading.value   = true
    isOpen.value      = true
    hasSearched.value = false

    try {
        const res  = await fetch(`/admin/teachers/search?q=${encodeURIComponent(q)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        const data: SearchResponse = await res.json()
        teachers.value    = data.teachers ?? []
        period.value      = data.period   ?? null
        hasSearched.value = true
    } catch {
        teachers.value = []
        period.value   = null
    } finally {
        isLoading.value = false
    }
}

/* ── Input handler ── */
function onInput() {
    clearTimeout(debounceTimer)
    activeIndex.value = -1

    if (query.value.trim().length < 2) {
        teachers.value    = []
        period.value      = null
        isOpen.value      = false
        hasSearched.value = false
        return
    }

    isLoading.value = true
    isOpen.value    = true
    debounceTimer   = setTimeout(performSearch, 350)
}

/* ── Actions ── */
function clear() {
    query.value       = ''
    teachers.value    = []
    period.value      = null
    isOpen.value      = false
    activeIndex.value = -1
    hasSearched.value = false
    inputRef.value?.focus()
}

function selectTeacher(teacher: Teacher) {
    query.value       = teacher.full_name
    isOpen.value      = false
    activeIndex.value = -1
    router.visit(route('admin.teachers.show', teacher.id))
}

/* ── Keyboard nav ── */
function onKeydown(e: KeyboardEvent) {
    if (!showDropdown.value) return
    if (e.key === 'ArrowDown') {
        e.preventDefault()
        activeIndex.value = Math.min(activeIndex.value + 1, teachers.value.length - 1)
        scrollToActive()
    } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        activeIndex.value = Math.max(activeIndex.value - 1, -1)
        scrollToActive()
    } else if (e.key === 'Escape') {
        isOpen.value = false
        inputRef.value?.blur()
    } else if (e.key === 'Enter') {
        e.preventDefault()
        if (activeIndex.value >= 0 && teachers.value[activeIndex.value]) {
            selectTeacher(teachers.value[activeIndex.value])
        }
    }
}

function scrollToActive() {
    document.getElementById(`search-result-${activeIndex.value}`)?.scrollIntoView({ block: 'nearest' })
}

function onFocus() {
    if (query.value.trim().length >= 2 && (hasSearched.value || isLoading.value)) {
        isOpen.value = true
    }
}

function onClickOutside(e: MouseEvent) {
    if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
        isOpen.value = false
    }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => {
    document.removeEventListener('mousedown', onClickOutside)
    clearTimeout(debounceTimer)
})
</script>

<template>
    <div ref="containerRef" class="global-search-wrapper relative">

        <!-- ── Input ── -->
        <div
            class="flex items-center gap-2 rounded-xl border px-3 py-2 transition-all duration-200"
            :class="showDropdown
                ? 'border-primary/60 bg-background shadow-md ring-2 ring-primary/20'
                : 'border-border/60 bg-muted/40 hover:border-border hover:bg-muted/60'"
        >
            <div class="shrink-0 text-muted-foreground">
                <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin text-primary" />
                <Search v-else class="h-4 w-4" />
            </div>

            <input
                ref="inputRef"
                v-model="query"
                type="text"
                placeholder="Buscar docente por nombre, DNI o correo..."
                class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground/70"
                autocomplete="off"
                spellcheck="false"
                @input="onInput"
                @keydown="onKeydown"
                @focus="onFocus"
            />

            <span v-if="!query" class="hidden shrink-0 items-center gap-0.5 lg:flex">
                <kbd class="rounded border border-border bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground leading-none">Ctrl</kbd>
                <kbd class="rounded border border-border bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground leading-none">K</kbd>
            </span>

            <button
                v-if="query"
                type="button"
                class="shrink-0 rounded-md p-0.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                @click="clear"
            >
                <X class="h-3.5 w-3.5" />
            </button>
        </div>

        <!-- ── Dropdown ── -->
        <Transition name="search-dropdown">
            <div
                v-if="showDropdown"
                class="absolute left-0 top-full z-50 mt-2 w-full min-w-[380px] overflow-hidden rounded-xl border border-border bg-background shadow-xl"
            >
                <!-- Cabecera de contexto de periodo -->
                <div
                    v-if="hasSearched && period"
                    class="flex items-center justify-between border-b border-border/60 bg-muted/30 px-4 py-2"
                >
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-1.5 w-1.5 shrink-0">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75" />
                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500" />
                        </span>
                        <span class="text-[11px] font-medium text-muted-foreground">
                            Periodo: <strong class="text-foreground">{{ period.name }}</strong>
                        </span>
                    </div>
                    <span class="text-[11px] text-muted-foreground/60">
                        {{ teachers.length }} docente{{ teachers.length !== 1 ? 's' : '' }}
                    </span>
                </div>

                <!-- Lista de resultados -->
                <ul v-if="teachers.length > 0" class="max-h-[400px] overflow-y-auto py-1.5" role="listbox">
                    <li
                        v-for="(teacher, idx) in teachers"
                        :id="`search-result-${idx}`"
                        :key="teacher.id"
                        role="option"
                        :aria-selected="activeIndex === idx"
                        class="mx-1.5 cursor-pointer rounded-lg px-2 py-2.5 transition-colors duration-100"
                        :class="activeIndex === idx ? 'bg-primary/10' : 'hover:bg-muted/60'"
                        @mouseenter="activeIndex = idx"
                        @mouseleave="activeIndex = -1"
                        @click="selectTeacher(teacher)"
                    >
                        <div class="flex items-center gap-3">
                            <!-- Avatar -->
                            <div
                                class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
                                :class="avatarColor(teacher.id)"
                            >
                                {{ getInitials(teacher.full_name) }}
                                <!-- Badge de vencidos -->
                                <span
                                    v-if="teacher.expired_in_period > 0"
                                    class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white ring-2 ring-background"
                                >
                                    {{ teacher.expired_in_period > 9 ? '9+' : teacher.expired_in_period }}
                                </span>
                            </div>

                            <!-- Info -->
                            <div class="min-w-0 flex-1">
                                <!-- Fila 1: nombre + estado -->
                                <div class="flex items-center gap-2">
                                    <span
                                        class="truncate text-sm font-semibold text-foreground"
                                        v-html="highlight(teacher.full_name)"
                                    />
                                    <span
                                        class="shrink-0 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium"
                                        :class="teacher.is_active
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
                                            : 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400'"
                                    >
                                        <CheckCircle2 v-if="teacher.is_active" class="h-2.5 w-2.5" />
                                        <XCircle v-else class="h-2.5 w-2.5" />
                                        {{ teacher.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>

                                <!-- Fila 2: DNI + correo -->
                                <div class="mt-0.5 flex flex-wrap items-center gap-3">
                                    <span class="flex items-center gap-1 text-xs text-muted-foreground">
                                        <Hash class="h-3 w-3 shrink-0" />
                                        <span v-html="highlight(teacher.dni)" />
                                    </span>
                                    <span
                                        v-if="teacher.email"
                                        class="flex min-w-0 items-center gap-1 text-xs text-muted-foreground"
                                    >
                                        <Mail class="h-3 w-3 shrink-0" />
                                        <span class="truncate max-w-[160px]" v-html="highlight(teacher.email)" />
                                    </span>
                                    <span v-else class="flex items-center gap-1 text-xs italic text-amber-500 dark:text-amber-400">
                                        <Mail class="h-3 w-3 shrink-0" />
                                        Sin correo
                                    </span>
                                </div>

                                <!-- Fila 3: stats del periodo -->
                                <div class="mt-1.5 flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-[10px] text-muted-foreground">
                                        {{ teacher.records_in_period }} registro{{ teacher.records_in_period !== 1 ? 's' : '' }} en periodo
                                    </span>
                                    <span
                                        v-if="teacher.expired_in_period > 0"
                                        class="inline-flex items-center gap-1 rounded-md bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400"
                                    >
                                        <AlertTriangle class="h-2.5 w-2.5" />
                                        {{ teacher.expired_in_period }} vencido{{ teacher.expired_in_period !== 1 ? 's' : '' }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                                    >
                                        Sin vencidos
                                    </span>
                                </div>
                            </div>

                            <!-- Chevron activo -->
                            <div
                                class="shrink-0 text-primary transition-opacity duration-100"
                                :class="activeIndex === idx ? 'opacity-100' : 'opacity-0'"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </li>
                </ul>

                <!-- Estado vacío -->
                <div v-else-if="hasSearched && !isLoading" class="flex flex-col items-center gap-2 px-4 py-8 text-center">
                    <UserRound class="h-10 w-10 text-muted-foreground/25" />
                    <p class="text-sm font-medium text-muted-foreground">Sin resultados en este periodo</p>
                    <p class="text-xs text-muted-foreground/60">
                        No hay docentes con actividad en
                        <strong>{{ period?.name ?? 'el periodo seleccionado' }}</strong>
                        que coincidan con tu búsqueda.
                    </p>
                </div>

                <!-- Skeleton de carga -->
                <div v-else-if="isLoading && !hasSearched" class="space-y-1 p-2">
                    <div v-for="i in 3" :key="i" class="flex items-center gap-3 rounded-lg px-2 py-2.5">
                        <div class="h-9 w-9 shrink-0 animate-pulse rounded-full bg-muted" />
                        <div class="flex-1 space-y-2">
                            <div class="h-3 w-2/3 animate-pulse rounded bg-muted" />
                            <div class="h-2.5 w-1/2 animate-pulse rounded bg-muted" />
                            <div class="h-2 w-1/3 animate-pulse rounded bg-muted" />
                        </div>
                    </div>
                </div>

                <!-- Footer con atajos -->
                <div v-if="teachers.length > 0" class="border-t border-border/50 px-3 py-2">
                    <p class="text-[11px] text-muted-foreground/60">
                        <kbd class="rounded border border-border bg-muted px-1 text-[10px]">↑</kbd>
                        <kbd class="rounded border border-border bg-muted px-1 text-[10px]">↓</kbd>
                        navegar &nbsp;·&nbsp;
                        <kbd class="rounded border border-border bg-muted px-1 text-[10px]">Enter</kbd>
                        abrir &nbsp;·&nbsp;
                        <kbd class="rounded border border-border bg-muted px-1 text-[10px]">Esc</kbd>
                        cerrar
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.global-search-wrapper {
    width: 100%;
    max-width: 480px;
}

:deep(mark) {
    background-color: oklch(0.97 0.15 87);
    color: oklch(0.4 0.12 60);
    border-radius: 2px;
    padding: 0 2px;
    font-weight: 600;
}
.dark :deep(mark) {
    background-color: oklch(0.35 0.1 70);
    color: oklch(0.92 0.12 75);
}

.search-dropdown-enter-active,
.search-dropdown-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.search-dropdown-enter-from,
.search-dropdown-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
}

ul::-webkit-scrollbar { width: 4px; }
ul::-webkit-scrollbar-track { background: transparent; }
ul::-webkit-scrollbar-thumb { background: hsl(var(--border)); border-radius: 99px; }
</style>
