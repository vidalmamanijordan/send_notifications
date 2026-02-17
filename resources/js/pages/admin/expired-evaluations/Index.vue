<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    records: any;
}>();
</script>

<template>
    <AppLayout>
        <Head title="Rubros Vencidos" />

        <div class="p-6">
            <h1 class="mb-4 text-xl font-semibold">
                Docentes con Rubros Vencidos
            </h1>

            <div class="rounded-xl bg-white shadow">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3 text-left">Periodo</th>
                            <th class="p-3 text-left">Campus</th>
                            <th class="p-3 text-left">Ciclo</th>
                            <th class="p-3 text-left">Grupo</th>
                            <th class="p-3 text-left">Rubros Vencidos</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="row in records.data" :key="row.id">
                            <td class="p-3">
                                {{ row.academic_period?.name }}
                            </td>
                            <td class="p-3">
                                {{ row.campus?.name }}
                            </td>
                            <td class="p-3">
                                {{ row.cycle }}
                            </td>
                            <td class="p-3">
                                {{ row.group }}
                            </td>
                            <td class="p-3 font-semibold text-red-600">
                                {{ row.expired_components }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex justify-end">
                <nav class="inline-flex gap-1">
                    <Link
                        v-for="link in records.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        class="rounded-md border px-3 py-1 text-sm"
                        :class="{
                            'bg-indigo-600 text-white': link.active,
                            'hover:bg-gray-100': !link.active && link.url,
                        }"
                    />
                </nav>
            </div>
        </div>
    </AppLayout>
</template>
