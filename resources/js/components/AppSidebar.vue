<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    Folder,
    LayoutGrid,
    School,
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import AppLogo from './AppLogo.vue';

/* 🔹 Navegación principal */
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        route: 'dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Configuración Académica',
        icon: School,
        children: [
            {
                title: 'Campus',
                route: 'admin.campus.index',
            },
            {
                title: 'Periodos académicos',
                route: 'admin.academic-periods.index',
            },
            {
                title: 'Facultades',
                route: 'admin.faculties.index',
            },
            {
                title: 'Programas   ',
                route: 'admin.programs.index',
            },
            {
                title: 'Cursos',
                route: 'admin.courses.index',
            },
        ],
    },
    {
        title: 'Gestión de evaluaciones',
        icon: BookOpen,
        children: [
            {
                title: 'Importar reporte',
                route: 'admin.excel-uploads.index',
            },
        ],
    },
    {
        title: 'Seguimiento Académico',
        icon: BarChart3,
        children: [
            {
                title: 'Rubros Vencidos',
                route: 'admin.expired-evaluations.index',
            },
        ],
    },
];

/* 🔹 Footer */
const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        route: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <!-- HEADER -->
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <!-- CONTENIDO -->
        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <!-- FOOTER -->
        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
