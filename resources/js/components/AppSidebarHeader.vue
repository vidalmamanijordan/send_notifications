<script setup lang="ts">
import AcademicPeriodSelector from '@/components/AcademicPeriodSelector.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { onMounted, onUnmounted, ref } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const searchRef = ref<InstanceType<typeof GlobalSearch> | null>(null);

function onGlobalKeydown(e: KeyboardEvent) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const input = searchRef.value?.$el?.querySelector('input');
        input?.focus();
    }
}

onMounted(() => document.addEventListener('keydown', onGlobalKeydown));
onUnmounted(() => document.removeEventListener('keydown', onGlobalKeydown));
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-3 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <!-- Izquierda: trigger + breadcrumbs -->
        <div class="flex shrink-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Centro: buscador global -->
        <div class="flex flex-1 items-center justify-center px-2">
            <GlobalSearch ref="searchRef" />
        </div>

        <!-- Derecha: selector de periodo académico -->
        <AcademicPeriodSelector />
    </header>
</template>
