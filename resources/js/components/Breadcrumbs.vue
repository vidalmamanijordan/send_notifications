<script setup lang="ts">
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

interface BreadcrumbItemType {
    title: string;
    href?: string;
}

defineProps<{
    breadcrumbs: BreadcrumbItemType[];
}>();

// 🔐 Obtener roles del usuario
const page = usePage();
const roles = computed(() => page.props.auth?.roles ?? []);

// ✅ Solo admin y superadmin pueden navegar a docentes
const canNavigateTeachers = computed(() => {
    return roles.value.includes('superadmin') || roles.value.includes('admin');
});
</script>

<template>
    <Breadcrumb>
        <BreadcrumbList>
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem>

                    <!-- Último item (no clickeable) -->
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage>
                            {{ item.title }}
                        </BreadcrumbPage>
                    </template>

                    <!-- Items anteriores -->
                    <template v-else>

                        <!-- 🔥 DOCENTES con permiso -->
                        <BreadcrumbLink
                            v-if="item.href?.includes('teachers') && canNavigateTeachers"
                            as-child
                        >
                            <Link :href="route('admin.teachers.index')">
                                {{ item.title }}
                            </Link>
                        </BreadcrumbLink>

                        <!-- ❌ DOCENTES sin permiso -->
                        <span
                            v-else-if="item.href?.includes('teachers')"
                            class="pointer-events-none text-gray-400"
                        >
                            {{ item.title }}
                        </span>

                        <!-- ✅ RESTO NORMAL -->
                        <BreadcrumbLink v-else as-child>
                            <Link :href="item.href ?? '#'">
                                {{ item.title }}
                            </Link>
                        </BreadcrumbLink>

                    </template>

                </BreadcrumbItem>

                <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>