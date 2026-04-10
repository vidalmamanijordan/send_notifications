<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh bg-white dark:bg-gray-950">

        <!-- ══════════════════════════════════════════════════════
             PANEL IZQUIERDO — Showcase animado de notificaciones
        ══════════════════════════════════════════════════════ -->
        <div class="auth-left relative hidden overflow-hidden lg:flex lg:w-[58%] lg:flex-col">

            <!-- Fondo degradado -->
            <div class="absolute inset-0" style="background: linear-gradient(145deg, #032f4a 0%, #04395a 40%, #065c8e 100%);" />

            <!-- Círculos decorativos de fondo -->
            <div class="absolute -top-32 -left-32 h-96 w-96 rounded-full opacity-10" style="background: radial-gradient(circle, #68c8fb, transparent);" />
            <div class="absolute -bottom-24 -right-24 h-80 w-80 rounded-full opacity-10" style="background: radial-gradient(circle, #68c8fb, transparent);" />
            <div class="absolute top-1/2 left-1/2 h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full opacity-5" style="background: radial-gradient(circle, #68c8fb, transparent);" />

            <!-- Grid de puntos decorativo -->
            <div class="absolute inset-0 opacity-[0.035]"
                style="background-image: radial-gradient(circle, #68c8fb 1px, transparent 1px); background-size: 32px 32px;" />

            <!-- ── CONTENIDO ──────────────────────────────── -->
            <div class="relative z-10 flex h-full flex-col px-12 py-10">

                <!-- Logo + nombre -->
                <Link :href="home()" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl" style="background: rgba(104,200,251,0.15); border: 1px solid rgba(104,200,251,0.3);">
                        <AppLogoIcon class="size-6 fill-current text-[#68c8fb]" />
                    </div>
                    <span class="text-lg font-semibold text-white">SisNotif</span>
                </Link>

                <!-- Titular central -->
                <div class="mt-auto mb-8">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full px-3 py-1.5" style="background: rgba(104,200,251,0.12); border: 1px solid rgba(104,200,251,0.25);">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#68c8fb]" />
                        <span class="text-xs font-medium text-[#68c8fb]">Sistema en línea</span>
                    </div>
                    <h1 class="mb-4 text-4xl font-bold leading-tight text-white">
                        Notificaciones<br />
                        <span style="color: #68c8fb;">inteligentes</span> para<br />
                        docentes
                    </h1>
                    <p class="max-w-md text-sm leading-relaxed" style="color: rgba(255,255,255,0.55);">
                        Gestiona y envía notificaciones masivas a docentes de manera eficiente. Plantillas personalizadas, lotes de correos y seguimiento en tiempo real.
                    </p>
                </div>

                <!-- ── TARJETAS DE NOTIFICACIÓN ANIMADAS ── -->
                <div class="relative mb-10 space-y-3">

                    <!-- Tarjeta 1 -->
                    <div class="notif-card notif-card-1 flex items-start gap-3 rounded-2xl px-4 py-3.5"
                         style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(8px);">
                        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" style="background: rgba(104,200,251,0.2);">
                            <svg class="h-4 w-4 text-[#68c8fb]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-white">Lote enviado correctamente</p>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" style="background: rgba(52,211,153,0.15); color: #34d399;">✓ 45 emails</span>
                            </div>
                            <p class="mt-0.5 text-xs" style="color: rgba(255,255,255,0.5);">Notificación de rubros vencidos · hace 2 min</p>
                        </div>
                    </div>

                    <!-- Tarjeta 2 -->
                    <div class="notif-card notif-card-2 flex items-start gap-3 rounded-2xl px-4 py-3.5"
                         style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(8px);">
                        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" style="background: rgba(251,191,36,0.15);">
                            <svg class="h-4 w-4" style="color: #fbbf24;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-white">Nuevo lote creado</p>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" style="background: rgba(251,191,36,0.12); color: #fbbf24;">Pendiente</span>
                            </div>
                            <p class="mt-0.5 text-xs" style="color: rgba(255,255,255,0.5);">Evaluaciones período 2024-II · 38 docentes</p>
                        </div>
                    </div>

                    <!-- Tarjeta 3 -->
                    <div class="notif-card notif-card-3 flex items-start gap-3 rounded-2xl px-4 py-3.5"
                         style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07); backdrop-filter: blur(8px);">
                        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" style="background: rgba(104,200,251,0.12);">
                            <svg class="h-4 w-4 text-[#68c8fb]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-white">Plantilla actualizada</p>
                                <span class="shrink-0 text-xs" style="color: rgba(255,255,255,0.35);">hace 1h</span>
                            </div>
                            <p class="mt-0.5 text-xs" style="color: rgba(255,255,255,0.5);">Aviso de rubro vencido · Oficina Académica</p>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl px-4 py-3 text-center" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
                        <p class="text-xl font-bold text-white">1.2k</p>
                        <p class="mt-0.5 text-xs" style="color: rgba(255,255,255,0.45);">Emails enviados</p>
                    </div>
                    <div class="rounded-2xl px-4 py-3 text-center" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
                        <p class="text-xl font-bold text-white">98%</p>
                        <p class="mt-0.5 text-xs" style="color: rgba(255,255,255,0.45);">Tasa de entrega</p>
                    </div>
                    <div class="rounded-2xl px-4 py-3 text-center" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
                        <p class="text-xl font-bold text-white">320</p>
                        <p class="mt-0.5 text-xs" style="color: rgba(255,255,255,0.45);">Docentes activos</p>
                    </div>
                </div>

                <!-- Separador + crédito -->
                <div class="mt-8 flex items-center gap-3">
                    <div class="h-px flex-1" style="background: rgba(255,255,255,0.1);" />
                    <p class="text-xs" style="color: rgba(255,255,255,0.3);">Universidad Peruana Unión</p>
                    <div class="h-px flex-1" style="background: rgba(255,255,255,0.1);" />
                </div>

            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════
             PANEL DERECHO — Formulario
        ══════════════════════════════════════════════════════ -->
        <div class="flex flex-1 flex-col items-center justify-center bg-white px-8 py-12 dark:bg-gray-950">

            <!-- Logo mobile (solo visible en móvil) -->
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl" style="background: #04395a;">
                    <AppLogoIcon class="size-5 fill-current text-[#68c8fb]" />
                </div>
                <span class="text-base font-semibold text-gray-900 dark:text-white">SisNotif</span>
            </div>

            <div class="w-full max-w-sm">
                <!-- Título del formulario -->
                <div class="mb-8 space-y-2">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ title }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ description }}
                    </p>
                </div>

                <!-- Slot con el formulario -->
                <slot />
            </div>

            <!-- Footer -->
            <p class="mt-12 text-xs text-gray-400 dark:text-gray-600">
                © {{ new Date().getFullYear() }} Universidad Peruana Unión · SisNotif
            </p>
        </div>

    </div>
</template>

<style scoped>
/* Animación de entrada para las tarjetas de notificación */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes floatCard {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-4px); }
}

.notif-card {
    animation: slideInUp 0.6s ease-out both;
}

.notif-card-1 {
    animation-delay: 0.2s;
    animation: slideInUp 0.6s ease-out 0.2s both, floatCard 6s ease-in-out 1s infinite;
}

.notif-card-2 {
    animation-delay: 0.4s;
    animation: slideInUp 0.6s ease-out 0.4s both, floatCard 7s ease-in-out 2s infinite;
}

.notif-card-3 {
    animation-delay: 0.6s;
    animation: slideInUp 0.6s ease-out 0.6s both, floatCard 8s ease-in-out 3s infinite;
}
</style>
