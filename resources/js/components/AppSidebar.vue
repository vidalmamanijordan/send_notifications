<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { type AppPageProps, type NavItem } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BarChart2,
    BarChart3,
    Bell,
    BookOpen,
    ChevronDown,
    ChevronRight,
    LayoutGrid,
    LogOut,
    School,
    Settings,
    User,
} from 'lucide-vue-next';
import { computed, ref, watchEffect } from 'vue';
import { route } from 'ziggy-js';
import logoAdventista from '../../images/siderbar/logo_oficial_adventista_white.svg';

const page = usePage<AppPageProps>();
const { state } = useSidebar();
const isCollapsed = computed(() => state.value === 'collapsed');

const user = computed(() => page.props.auth?.user);
const roles = computed(() => page.props.auth?.roles ?? []);
const perms = computed(
    () => new Set<string>(page.props.auth?.permissions ?? []),
);
const isSuperadmin = computed(() => roles.value.includes('superadmin'));
const can = (permission: string) =>
    isSuperadmin.value || perms.value.has(permission);

const getInitials = (name: string) =>
    name
        ?.split(' ')
        .map((w: string) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase() ?? '?';

// ── Navegación ─────────────────────────────────────────────────────────
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'Dashboard', route: 'dashboard', icon: LayoutGrid },
    ];

    const adminChildren: NavItem[] = [];
    if (can('users.viewAny')) {
        adminChildren.push({ title: 'Usuarios', route: 'admin.users.index' });
    }
    if (isSuperadmin.value) {
        adminChildren.push({
            title: 'Roles y Permisos',
            route: 'admin.roles.index',
        });
    }
    if (adminChildren.length > 0) {
        items.push({
            title: 'Administrar',
            icon: Settings,
            children: adminChildren,
        });
    }

    const academicChildren: NavItem[] = [];
    if (can('campus.viewAny')) {
        academicChildren.push({ title: 'Campus', route: 'admin.campus.index' });
    }
    if (can('academicPeriods.viewAny')) {
        academicChildren.push({
            title: 'Periodos académicos',
            route: 'admin.academic-periods.index',
        });
    }
    if (can('faculties.viewAny')) {
        academicChildren.push({
            title: 'Facultades',
            route: 'admin.faculties.index',
        });
    }
    if (can('programs.viewAny')) {
        academicChildren.push({
            title: 'Programas',
            route: 'admin.programs.index',
        });
    }
    if (can('courses.viewAny')) {
        academicChildren.push({
            title: 'Cursos',
            route: 'admin.courses.index',
        });
    }
    if (can('teachers.viewAny')) {
        academicChildren.push({
            title: 'Docentes',
            route: 'admin.teachers.index',
        });
    }
    if (academicChildren.length > 0) {
        items.push({
            title: 'Config. Académica',
            icon: School,
            children: academicChildren,
        });
    }

    const evalChildren: NavItem[] = [];
    if (can('excelUploads.viewAny')) {
        evalChildren.push({
            title: 'Importar Rub. Venc.',
            route: 'admin.excel-uploads.index',
        });
    }
    if (can('teachers.viewAny')) {
        evalChildren.push({
            title: 'Difusión a Docentes',
            route: 'admin.persons.index',
        });
    }
    if (evalChildren.length > 0) {
        items.push({
            title: 'Evaluaciones',
            icon: BookOpen,
            children: evalChildren,
        });
    }

    if (can('expiredEvaluations.viewAny')) {
        items.push({
            title: 'Seguimiento',
            icon: BarChart3,
            children: [
                {
                    title: 'Rubros Vencidos',
                    route: 'admin.expired-evaluations.index',
                },
            ],
        });
    }

    const notifChildren: NavItem[] = [];
    if (can('offices.viewAny')) {
        notifChildren.push({ title: 'Oficinas', route: 'admin.offices.index' });
    }
    if (can('notificationTemplates.viewAny')) {
        notifChildren.push({
            title: 'Plantillas',
            route: 'admin.notification-templates.index',
        });
    }
    if (can('notificationBatches.viewAny')) {
        notifChildren.push({
            title: 'Lotes activos',
            route: 'admin.notification-batches.index',
        });
    }
    if (notifChildren.length > 0) {
        items.push({
            title: 'Notificaciones',
            icon: Bell,
            children: notifChildren,
        });
    }

    if (can('notificationBatches.viewAny')) {
        items.push({
            title: 'Reportes',
            icon: BarChart2,
            route: 'admin.reports.index',
        });
    }

    return items;
});

// ── Estado de submenús ─────────────────────────────────────────────────
const open = ref<Record<string, boolean>>({});

const isActive = (routeName?: string) =>
    routeName ? route().current(routeName) : false;
const isAnyChildActive = (children?: NavItem[]) =>
    children?.some((c) => (c.route ? route().current(c.route + '*') : false)) ??
    false;

watchEffect(() => {
    mainNavItems.value.forEach((item) => {
        if (item.children && !(item.title in open.value)) {
            open.value[item.title] = isAnyChildActive(item.children);
        }
    });
});

const handleLogout = () => {
    router.flushAll();
    router.post(route('logout'));
};
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <div class="relative flex min-h-0 flex-1 flex-col">
            <!-- ══════════════════════════════════════════════════════
             MARCA DE AGUA — Logo inclinado
        ══════════════════════════════════════════════════════ -->
            <div
                class="pointer-events-none absolute inset-0 overflow-hidden"
                style="z-index: 0"
            >
                <img
                    :src="logoAdventista"
                    alt=""
                    aria-hidden="true"
                    class="absolute select-none"
                    style="
                        width: 5000px;
                        height: auto;
                        left: 50%;
                        top: 50%;
                        transform: translate(-50%, -50%) rotate(-12deg);
                        opacity: 0.09;
                        filter: brightness(10);
                    "
                />
            </div>

            <!-- ══════════════════════════════════════════════════════
             HEADER — Logo
        ══════════════════════════════════════════════════════ -->
            <SidebarHeader
                class="overflow-hidden p-0"
                style="position: relative; z-index: 1"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_0%,rgba(104,200,251,0.18),transparent)]"
                />

                <div class="relative px-3 py-4">
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <Link
                                :href="route('dashboard')"
                                class="flex items-center gap-3"
                            >
                                <AppLogo />
                            </Link>
                        </SidebarMenuItem>
                    </SidebarMenu>

                    <!-- Separador decorativo -->
                    <div
                        v-if="!isCollapsed"
                        class="mt-4 flex items-center gap-2"
                    >
                        <div
                            class="h-px flex-1 bg-gradient-to-r from-transparent via-white/20 to-transparent"
                        />
                        <div class="h-1 w-1 rounded-full bg-[#68c8fb]/60" />
                        <div
                            class="h-px flex-1 bg-gradient-to-r from-transparent via-white/20 to-transparent"
                        />
                    </div>
                </div>
            </SidebarHeader>

            <!-- ══════════════════════════════════════════════════════
             CONTENIDO — Navegación
        ══════════════════════════════════════════════════════ -->
            <SidebarContent
                class="px-2 py-2"
                style="position: relative; z-index: 1"
            >
                <SidebarMenu>
                    <SidebarMenuItem
                        v-for="item in mainNavItems"
                        :key="item.title"
                        class="mb-0.5"
                    >
                        <!-- ITEM SIMPLE (sin hijos) -->
                        <Link
                            v-if="!item.children"
                            :href="route(item.route!)"
                            class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
                            :class="
                                isActive(item.route)
                                    ? 'bg-white/14 text-white shadow-sm ring-1 shadow-black/10 ring-white/10'
                                    : 'text-white/75 hover:bg-white/8 hover:text-white'
                            "
                        >
                            <!-- Indicador activo izquierda -->
                            <div
                                class="absolute left-0 h-6 w-0.5 rounded-full bg-[#68c8fb] transition-all duration-200"
                                :class="
                                    isActive(item.route)
                                        ? 'opacity-100'
                                        : 'opacity-0'
                                "
                            />
                            <component
                                :is="item.icon"
                                class="h-4 w-4 shrink-0 transition-colors"
                                :class="
                                    isActive(item.route)
                                        ? 'text-[#68c8fb]'
                                        : 'text-white/60 group-hover:text-white/90'
                                "
                            />
                            <span
                                v-if="!isCollapsed"
                                class="truncate leading-none"
                                >{{ item.title }}</span
                            >
                        </Link>

                        <!-- ITEM CON SUBMENÚ -->
                        <div v-else class="relative">
                            <button
                                type="button"
                                @click="open[item.title] = !open[item.title]"
                                class="group relative flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
                                :class="
                                    isAnyChildActive(item.children)
                                        ? 'bg-white/14 text-white shadow-sm ring-1 shadow-black/10 ring-white/10'
                                        : 'text-white/75 hover:bg-white/8 hover:text-white'
                                "
                            >
                                <!-- Indicador activo izquierda -->
                                <div
                                    class="absolute left-0 h-6 w-0.5 rounded-full bg-[#68c8fb] transition-all duration-200"
                                    :class="
                                        isAnyChildActive(item.children)
                                            ? 'opacity-100'
                                            : 'opacity-0'
                                    "
                                />
                                <component
                                    :is="item.icon"
                                    class="h-4 w-4 shrink-0 transition-colors"
                                    :class="
                                        isAnyChildActive(item.children)
                                            ? 'text-[#68c8fb]'
                                            : 'text-white/60 group-hover:text-white/90'
                                    "
                                />
                                <span
                                    v-if="!isCollapsed"
                                    class="flex-1 truncate text-left leading-none"
                                    >{{ item.title }}</span
                                >
                                <ChevronDown
                                    v-if="!isCollapsed"
                                    class="h-3.5 w-3.5 shrink-0 text-white/40 transition-transform duration-200"
                                    :class="{ 'rotate-180': open[item.title] }"
                                />
                            </button>

                            <!-- SUBMENÚ -->
                            <div
                                v-if="!isCollapsed && open[item.title]"
                                class="mt-0.5 overflow-hidden"
                            >
                                <div class="ml-3 border-l border-white/10 pl-3">
                                    <Link
                                        v-for="child in item.children"
                                        :key="child.title"
                                        :href="route(child.route!)"
                                        class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition-all duration-150"
                                        :class="
                                            isActive(child.route)
                                                ? 'bg-white/12 font-medium text-white'
                                                : 'text-white/60 hover:bg-white/6 hover:text-white/90'
                                        "
                                    >
                                        <div
                                            class="h-1.5 w-1.5 shrink-0 rounded-full transition-colors"
                                            :class="
                                                isActive(child.route)
                                                    ? 'bg-[#68c8fb]'
                                                    : 'bg-white/25 group-hover:bg-white/50'
                                            "
                                        />
                                        <span class="truncate leading-none">{{
                                            child.title
                                        }}</span>
                                    </Link>
                                </div>
                            </div>

                            <!-- Submenú colapsado: tooltip / indicador -->
                            <div
                                v-else-if="isCollapsed"
                                class="mt-0.5 space-y-0.5"
                            >
                                <Link
                                    v-for="child in item.children"
                                    :key="child.title"
                                    :href="route(child.route!)"
                                    :title="child.title"
                                    class="flex h-7 w-7 items-center justify-center rounded-lg transition-all duration-150"
                                    :class="
                                        isActive(child.route)
                                            ? 'bg-white/14 text-[#68c8fb]'
                                            : 'text-white/40 hover:bg-white/8 hover:text-white/70'
                                    "
                                >
                                    <ChevronRight class="h-3 w-3" />
                                </Link>
                            </div>
                        </div>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarContent>

            <!-- ══════════════════════════════════════════════════════
             FOOTER — Usuario
        ══════════════════════════════════════════════════════ -->
            <SidebarFooter class="p-3" style="position: relative; z-index: 1">
                <!-- Separador -->
                <div
                    class="mb-3 h-px bg-gradient-to-r from-transparent via-white/15 to-transparent"
                />

                <!-- Card de usuario -->
                <div
                    class="group flex items-center gap-3 rounded-xl p-2.5 transition-all duration-200 hover:bg-white/8"
                    :class="isCollapsed ? 'justify-center' : ''"
                >
                    <!-- Avatar -->
                    <div class="relative shrink-0">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#068ab8] text-xs font-bold text-white shadow-md ring-2 shadow-black/20 ring-[#68c8fb]/30"
                        >
                            {{ getInitials(user?.name ?? '') }}
                        </div>
                        <div
                            class="absolute -right-0.5 -bottom-0.5 h-2.5 w-2.5 rounded-full border-2 border-[#04395a] bg-emerald-400"
                        />
                    </div>

                    <!-- Info usuario -->
                    <div v-if="!isCollapsed" class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm leading-tight font-semibold text-white"
                        >
                            {{ user?.name }}
                        </p>
                        <p
                            class="mt-0.5 truncate text-xs leading-none text-white/45"
                        >
                            {{ user?.email }}
                        </p>
                    </div>

                    <!-- Botón logout -->
                    <button
                        v-if="!isCollapsed"
                        @click="handleLogout"
                        title="Cerrar sesión"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-white/35 transition-all hover:bg-red-500/20 hover:text-red-400"
                    >
                        <LogOut class="h-3.5 w-3.5" />
                    </button>
                </div>

                <!-- Settings link -->
                <Link
                    v-if="!isCollapsed"
                    :href="route('profile.edit')"
                    class="mt-1 flex items-center gap-2 rounded-xl px-3 py-2 text-xs text-white/40 transition-all hover:bg-white/6 hover:text-white/70"
                >
                    <User class="h-3.5 w-3.5" />
                    Configuración de perfil
                </Link>
            </SidebarFooter>
        </div>
    </Sidebar>
</template>
