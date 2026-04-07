<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { CalendarDays, Check, ChevronDown, Circle } from 'lucide-vue-next'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { route } from 'ziggy-js'

interface AcademicPeriod {
    id: number
    name: string
    status: string
}

/* ── Shared props desde el middleware ── */
const page = usePage()
const periods = computed<AcademicPeriod[]>(() => (page.props as any).academicPeriods ?? [])
const current = computed<AcademicPeriod | null>(() => (page.props as any).currentPeriod ?? null)

/* ── Estado local ── */
const isOpen = ref(false)
const isSwitching = ref(false)
const containerRef = ref<HTMLDivElement | null>(null)

/* ── Cerrar al click exterior ── */
function onClickOutside(e: MouseEvent) {
    if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
        isOpen.value = false
    }
}
onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))

/* ── Cambiar periodo ── */
function selectPeriod(period: AcademicPeriod) {
    if (current.value?.id === period.id) {
        isOpen.value = false
        return
    }

    isSwitching.value = true
    isOpen.value = false

    router.post(
        route('admin.switch-period'),
        { academic_period_id: period.id },
        {
            preserveScroll: true,
            onSuccess: () => router.reload({ preserveScroll: true }),
            onFinish: () => { isSwitching.value = false },
        },
    )
}

const isActive = (p: AcademicPeriod) => p.status === 'active'
const isCurrent = (p: AcademicPeriod) => p.id === current.value?.id
</script>

<template>
    <div ref="containerRef" class="relative shrink-0">

        <!-- ── Trigger pill ── -->
        <button
            type="button"
            class="period-trigger group flex items-center gap-2 rounded-xl border px-3 py-2 text-sm transition-all duration-200 select-none"
            :class="[
                isOpen
                    ? 'border-primary/60 bg-background shadow-md ring-2 ring-primary/20'
                    : 'border-border/60 bg-muted/40 hover:border-border hover:bg-muted/60',
                isSwitching && 'opacity-70 pointer-events-none',
            ]"
            @click="isOpen = !isOpen"
        >
            <!-- Dot de estado del periodo actual -->
            <span class="relative flex h-2 w-2 shrink-0">
                <span
                    v-if="current?.status === 'active'"
                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                />
                <span
                    class="relative inline-flex h-2 w-2 rounded-full"
                    :class="current?.status === 'active' ? 'bg-emerald-500' : 'bg-gray-400'"
                />
            </span>

            <!-- Icono y nombre -->
            <CalendarDays class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />

            <span class="max-w-[140px] truncate font-medium text-foreground">
                {{ current?.name ?? 'Sin periodo' }}
            </span>

            <!-- Chevron -->
            <ChevronDown
                class="h-3.5 w-3.5 shrink-0 text-muted-foreground transition-transform duration-200"
                :class="isOpen && 'rotate-180'"
            />
        </button>

        <!-- ── Dropdown ── -->
        <Transition name="period-dropdown">
            <div
                v-if="isOpen"
                class="absolute right-0 top-full z-50 mt-2 w-64 overflow-hidden rounded-xl border border-border bg-background shadow-xl"
            >
                <!-- Header -->
                <div class="border-b border-border/60 px-4 py-2.5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Periodo académico
                    </p>
                    <p class="text-[11px] text-muted-foreground/60 mt-0.5">
                        Filtra todas las vistas por periodo
                    </p>
                </div>

                <!-- Lista de periodos -->
                <ul class="max-h-72 overflow-y-auto py-1.5">
                    <li
                        v-for="period in periods"
                        :key="period.id"
                        class="mx-1.5 flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 transition-colors duration-100"
                        :class="[
                            isCurrent(period)
                                ? 'bg-primary/10'
                                : 'hover:bg-muted/60',
                        ]"
                        @click="selectPeriod(period)"
                    >
                        <!-- Estado del periodo -->
                        <span class="relative flex h-2 w-2 shrink-0">
                            <span
                                v-if="isActive(period)"
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                            />
                            <span
                                class="relative inline-flex h-2 w-2 rounded-full"
                                :class="isActive(period) ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600'"
                            />
                        </span>

                        <!-- Nombre -->
                        <div class="flex min-w-0 flex-1 flex-col">
                            <span
                                class="truncate text-sm font-medium"
                                :class="isCurrent(period) ? 'text-primary' : 'text-foreground'"
                            >
                                {{ period.name }}
                            </span>
                            <span
                                class="text-[10px]"
                                :class="isActive(period) ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground/60'"
                            >
                                {{ isActive(period) ? 'Periodo activo' : 'Histórico' }}
                            </span>
                        </div>

                        <!-- Checkmark si es el seleccionado -->
                        <Check
                            v-if="isCurrent(period)"
                            class="h-4 w-4 shrink-0 text-primary"
                        />
                    </li>

                    <li v-if="periods.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
                        No hay periodos registrados
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.period-dropdown-enter-active,
.period-dropdown-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.period-dropdown-enter-from,
.period-dropdown-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
}

ul::-webkit-scrollbar { width: 4px; }
ul::-webkit-scrollbar-track { background: transparent; }
ul::-webkit-scrollbar-thumb { background: hsl(var(--border)); border-radius: 99px; }
</style>
