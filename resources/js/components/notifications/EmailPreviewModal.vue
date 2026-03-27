```vue
<script setup lang="ts">
import { X, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    show: boolean;
    subject: string;
    body: string;
    officeName: string;
    officeEmail: string;
    officeSignature?: string;
    sentAt: string;
    emails?: string[];
    teachers?: { id: number; name: string; email: string }[];
}>();

const emit = defineEmits(['close']);

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('es-PE', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
};

const initial = computed(
    () => props.officeName?.charAt(0).toUpperCase() ?? 'O',
);

const emailsText = computed(() => {
    if (!props.emails || props.emails.length === 0) {
        return 'Docentes';
    }

    if (props.emails.length <= 3) {
        return props.emails.join(', ');
    }

    return `${props.emails.slice(0, 3).join(', ')} y ${props.emails.length - 3} más`;
});

/* 👇 BODY + FIRMA */
const fullBody = computed(() => {
    let content = (props.body ?? '').replace(/\n+$/g, '');

    if (props.officeSignature) {
        content += `
            <div style="margin-top:10px;">
                <img src="/storage/${props.officeSignature}" 
                     style="max-height:90px; object-fit:contain;" />
            </div>
        `;
    }

    return content;
});

const truncateName = (name: string, max = 20) => {
    return name.length > max ? name.slice(0, max) + '...' : name;
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    >
        <div
            class="max-h-[90vh] w-[900px] overflow-hidden rounded-xl bg-white shadow-2xl"
        >
            <!-- HEADER -->
            <div
                class="flex items-center justify-between border-b bg-gray-50 px-6 py-3"
            >
                <span class="text-sm font-semibold text-gray-700">
                    Vista previa del correo
                </span>

                <button
                    @click="emit('close')"
                    class="rounded p-1 text-gray-500 transition hover:bg-gray-200"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- CONTENIDO -->
            <div class="px-10 py-8">
                <!-- REMITENTE -->
                <div class="flex items-start gap-4">
                    <!-- AVATAR -->
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-500 text-sm font-semibold text-white shadow"
                    >
                        {{ initial }}
                    </div>

                    <div class="flex-1">
                        <!-- NOMBRE / CORREO / FECHA -->
                        <div class="flex items-start justify-between">
                            <div class="flex flex-col">
                                <span
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    {{ officeName }}
                                </span>

                                <span class="text-sm text-gray-500">
                                    &lt;{{ officeEmail }}&gt;
                                </span>
                            </div>

                            <span class="text-xs text-gray-500">
                                {{ formatDate(sentAt) }}
                            </span>
                        </div>

                        <div class="mt-2 flex items-start gap-2 text-sm">
                            <span class="mt-[3px] text-gray-500">Para:</span>

                            <div class="flex flex-wrap gap-1">
                                <!-- Cuando no hay docentes -->
                                <span
                                    v-if="!teachers || teachers.length === 0"
                                    class="flex items-center gap-1 rounded-md bg-gray-100 px-2 py-[3px] text-xs font-medium text-gray-800 transition hover:bg-gray-200"
                                >
                                    <XCircle class="h-3 w-3 text-gray-500" />
                                    Docentes
                                </span>

                                <!-- Chips de docentes -->
                                <span
                                    v-else
                                    v-for="teacher in teachers"
                                    :key="teacher.id"
                                    class="flex items-center gap-1 rounded-md bg-gray-100 px-2 py-[3px] text-xs font-medium text-gray-800 transition hover:bg-gray-200"
                                >
                                    <XCircle class="h-3 w-3 text-gray-500" />

                                    {{ truncateName(teacher.name) }}
                                </span>
                            </div>
                        </div>

                        <!-- ASUNTO -->
                        <div class="mt-6 border-b pb-3">
                            <span
                                class="text-[16px] font-semibold text-gray-900"
                            >
                                {{ subject }}
                            </span>
                        </div>

                        <!-- BODY + FIRMA -->
                        <div
                            class="mt-4 max-h-[45vh] overflow-y-auto pr-3 text-[14px] leading-relaxed whitespace-pre-line text-gray-800"
                            v-html="fullBody"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
```
