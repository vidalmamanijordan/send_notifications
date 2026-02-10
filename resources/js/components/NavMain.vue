<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar'

import { type NavItem } from '@/types'
import { Link } from '@inertiajs/vue3'
import { ref, watchEffect } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import { route } from 'ziggy-js'

/* =========================
   Props
========================= */
const props = defineProps<{
    items: NavItem[]
}>()

/* =========================
   Control de submenús
========================= */
const open = ref<Record<string, boolean>>({})

/* =========================
   Ruta activa directa
========================= */
const isActive = (routeName?: string) => {
    if (!routeName) return false
    return route().current(routeName)
}

/* =========================
   ¿Algún hijo activo?
========================= */
const isAnyChildActive = (children?: NavItem[]) => {
    if (!children) return false

    return children.some(child =>
        child.route ? route().current(child.route + '*') : false
    )
}

/* =========================
   Abrir submenú automáticamente
========================= */
watchEffect(() => {
    props.items.forEach(item => {
        if (item.children) {
            open.value[item.title] = isAnyChildActive(item.children)
        }
    })
})
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>

        <SidebarMenu>
            <SidebarMenuItem
                v-for="item in props.items"
                :key="item.title"
            >
                <!-- ITEM SIMPLE -->
                <SidebarMenuButton
                    v-if="!item.children"
                    as-child
                    :is-active="isActive(item.route)"
                >
                    <Link :href="route(item.route!)">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>

                <!-- ITEM CON SUBMENÚ -->
                <div v-else>
                    <SidebarMenuButton
                        @click="open[item.title] = !open[item.title]"
                        :is-active="isAnyChildActive(item.children)"
                    >
                        <component :is="item.icon" />
                        <span class="flex-1">{{ item.title }}</span>

                        <ChevronDown
                            class="h-4 w-4 transition-transform"
                            :class="{ 'rotate-180': open[item.title] }"
                        />
                    </SidebarMenuButton>

                    <!-- SUBMENÚ -->
                    <SidebarMenu
                        v-show="open[item.title]"
                        class="ml-6 mt-1 space-y-1"
                    >
                        <SidebarMenuItem
                            v-for="child in item.children"
                            :key="child.title"
                        >
                            <SidebarMenuButton
                                as-child
                                size="sm"
                                :is-active="isActive(child.route)"
                            >
                                <Link :href="route(child.route!)">
                                    <span>{{ child.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </div>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
