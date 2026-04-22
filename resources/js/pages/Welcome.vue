<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard, login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="SisNotif — Sistema de Notificaciones">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-white font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-white">

        <!-- ══════════════════════════════════════════════════════
             NAVBAR
        ══════════════════════════════════════════════════════ -->
        <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10"
                style="background: rgba(4,57,90,0.97); backdrop-filter: blur(12px);">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl"
                         style="background: rgba(104,200,251,0.15); border: 1px solid rgba(104,200,251,0.3);">
                        <AppLogoIcon class="size-5 fill-current" style="color: #68c8fb;" />
                    </div>
                    <div>
                        <p class="text-sm font-bold leading-none text-white">SisNotif</p>
                        <p class="text-[10px] leading-none" style="color: rgba(255,255,255,0.4);">UPeU</p>
                    </div>
                </div>

                <!-- Nav links -->
                <nav class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white transition-all hover:opacity-90"
                        style="background: #68c8fb; color: #04395a;"
                    >
                        Ir al Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-xl px-4 py-2 text-sm font-semibold transition-all hover:opacity-90"
                            style="background: #68c8fb; color: #04395a;"
                        >
                            Iniciar sesión
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- ══════════════════════════════════════════════════════
             HERO
        ══════════════════════════════════════════════════════ -->
        <section class="relative overflow-hidden pt-16"
                 style="background: linear-gradient(160deg, #032f4a 0%, #04395a 45%, #065c8e 100%); min-height: 92vh;">

            <!-- Decoración de fondo -->
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute -top-32 right-0 h-[500px] w-[500px] rounded-full opacity-10"
                     style="background: radial-gradient(circle, #68c8fb, transparent);" />
                <div class="absolute bottom-0 left-0 h-80 w-80 rounded-full opacity-10"
                     style="background: radial-gradient(circle, #68c8fb, transparent);" />
                <div class="absolute inset-0 opacity-[0.03]"
                     style="background-image: radial-gradient(circle, #68c8fb 1px, transparent 1px); background-size: 36px 36px;" />
                <!-- Líneas decorativas -->
                <svg class="absolute right-0 top-1/4 h-64 w-64 opacity-5" viewBox="0 0 200 200" fill="none">
                    <circle cx="100" cy="100" r="80" stroke="#68c8fb" stroke-width="1"/>
                    <circle cx="100" cy="100" r="55" stroke="#68c8fb" stroke-width="1"/>
                    <circle cx="100" cy="100" r="30" stroke="#68c8fb" stroke-width="1"/>
                </svg>
            </div>

            <div class="relative mx-auto flex max-w-7xl flex-col items-center px-6 py-24 text-center lg:py-36">

                <!-- Badge -->
                <div class="mb-6 inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-widest"
                     style="background: rgba(104,200,251,0.1); border: 1px solid rgba(104,200,251,0.25); color: #68c8fb;">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#68c8fb]" />
                    Sistema Activo — Universidad Peruana Unión
                </div>

                <!-- Titular -->
                <h1 class="mb-6 max-w-4xl text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Gestión inteligente de<br />
                    <span style="color: #68c8fb;">notificaciones</span> para docentes
                </h1>

                <p class="mb-10 max-w-2xl text-lg leading-relaxed" style="color: rgba(255,255,255,0.6);">
                    Automatiza el envío de correos a docentes sobre rubros vencidos, evaluaciones y avisos académicos.
                    Plantillas personalizables, lotes masivos y seguimiento en tiempo real.
                </p>

                <!-- CTAs -->
                <div class="flex flex-wrap items-center justify-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="flex items-center gap-2 rounded-2xl px-7 py-3.5 text-base font-bold shadow-lg transition-all hover:scale-105 hover:shadow-xl"
                        style="background: #68c8fb; color: #04395a;"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Ir al Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="flex items-center gap-2 rounded-2xl px-7 py-3.5 text-base font-bold shadow-lg transition-all hover:scale-105 hover:shadow-xl"
                            style="background: #68c8fb; color: #04395a;"
                        >
                            Iniciar sesión
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </Link>
                    </template>
                </div>

                <!-- Estadísticas rápidas -->
                <div class="mt-20 grid grid-cols-2 gap-6 sm:grid-cols-4">
                    <div v-for="stat in [
                        { value: '100%', label: 'Automatizado' },
                        { value: 'Multi', label: 'Campus' },
                        { value: 'Email', label: 'Notificaciones' },
                        { value: 'Real', label: 'Tiempo real' },
                    ]" :key="stat.label"
                         class="rounded-2xl px-6 py-4 text-center"
                         style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
                        <p class="text-2xl font-extrabold text-white">{{ stat.value }}</p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-wide" style="color: rgba(255,255,255,0.45);">{{ stat.label }}</p>
                    </div>
                </div>
            </div>

            <!-- Wave separator -->
            <div class="absolute bottom-0 left-0 right-0">
                <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 60L1440 60L1440 20C1200 60 960 0 720 20C480 40 240 0 0 20L0 60Z" fill="white" class="dark:fill-gray-950"/>
                </svg>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════
             FUNCIONALIDADES
        ══════════════════════════════════════════════════════ -->
        <section class="bg-white py-24 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-6">

                <div class="mb-16 text-center">
                    <span class="mb-3 inline-block rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-widest"
                          style="background: rgba(4,57,90,0.07); color: #04395a;">
                        ¿Qué hace el sistema?
                    </span>
                    <h2 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                        Todo lo que necesitas para gestionar<br />las notificaciones docentes
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-base text-gray-500 dark:text-gray-400">
                        Desde la importación del reporte Excel hasta el envío masivo de correos, el sistema cubre todo el flujo.
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="feature in [
                        {
                            icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                            title: 'Importación de reportes',
                            desc: 'Carga archivos Excel con datos de evaluaciones docentes. El sistema procesa automáticamente el reporte y detecta rubros vencidos.',
                            color: '#68c8fb',
                            bg: 'rgba(104,200,251,0.08)',
                            border: 'rgba(104,200,251,0.2)',
                        },
                        {
                            icon: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
                            title: 'Plantillas personalizables',
                            desc: 'Crea y edita plantillas de correo con variables dinámicas. Cada oficina puede tener su propia plantilla con estilos personalizados.',
                            color: '#10b981',
                            bg: 'rgba(16,185,129,0.08)',
                            border: 'rgba(16,185,129,0.2)',
                        },
                        {
                            icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                            title: 'Envío masivo de correos',
                            desc: 'Genera lotes de notificaciones y envíalos a todos los docentes con rubros vencidos de forma automática y controlada.',
                            color: '#04395a',
                            bg: 'rgba(4,57,90,0.06)',
                            border: 'rgba(4,57,90,0.15)',
                        },
                        {
                            icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                            title: 'Reportes y estadísticas',
                            desc: 'Dashboard con gráficos interactivos: tasa de entrega, distribución por campus, tendencias de envío y docentes con más vencidos.',
                            color: '#f59e0b',
                            bg: 'rgba(245,158,11,0.08)',
                            border: 'rgba(245,158,11,0.2)',
                        },
                        {
                            icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                            title: 'Gestión de docentes',
                            desc: 'Administra el directorio de docentes, vincula cuentas de usuario, asigna roles y controla el acceso por campus y facultad.',
                            color: '#8b5cf6',
                            bg: 'rgba(139,92,246,0.08)',
                            border: 'rgba(139,92,246,0.2)',
                        },
                        {
                            icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                            title: 'Seguimiento individual',
                            desc: 'Reenvía notificaciones individuales a docentes específicos, revisa el historial de cada correo enviado y detecta fallos de entrega.',
                            color: '#ef4444',
                            bg: 'rgba(239,68,68,0.08)',
                            border: 'rgba(239,68,68,0.2)',
                        },
                    ]" :key="feature.title"
                         class="group rounded-2xl p-6 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg"
                         :style="`background: ${feature.bg}; border: 1px solid ${feature.border};`">
                        <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl"
                             :style="`background: ${feature.bg}; border: 1px solid ${feature.border};`">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                 :style="`color: ${feature.color};`">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="feature.icon"/>
                            </svg>
                        </div>
                        <h3 class="mb-2 text-base font-bold text-gray-900 dark:text-white">{{ feature.title }}</h3>
                        <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ feature.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════
             CÓMO FUNCIONA
        ══════════════════════════════════════════════════════ -->
        <section class="py-24 dark:bg-gray-900" style="background: #f8fafc;">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mb-16 text-center">
                    <span class="mb-3 inline-block rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-widest"
                          style="background: rgba(4,57,90,0.07); color: #04395a;">
                        Flujo de trabajo
                    </span>
                    <h2 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                        ¿Cómo funciona?
                    </h2>
                </div>

                <div class="relative">
                    <!-- Línea conectora (solo desktop) -->
                    <div class="absolute left-0 right-0 top-8 hidden h-0.5 lg:block"
                         style="background: linear-gradient(to right, transparent, #68c8fb, #04395a, #68c8fb, transparent);" />

                    <div class="grid gap-8 lg:grid-cols-4">
                        <div v-for="(step, i) in [
                            { num: '01', title: 'Importa el reporte', desc: 'Sube el archivo Excel con las evaluaciones de docentes del periodo académico.' },
                            { num: '02', title: 'Crea una plantilla', desc: 'Diseña la plantilla de correo que se enviará con los detalles del aviso académico.' },
                            { num: '03', title: 'Genera el lote', desc: 'El sistema detecta automáticamente los docentes con rubros vencidos y genera el lote.' },
                            { num: '04', title: 'Envía y monitorea', desc: 'Envía masivamente y monitorea el estado de cada correo en tiempo real desde el dashboard.' },
                        ]" :key="i"
                             class="relative flex flex-col items-center text-center">
                            <div class="relative z-10 mb-5 flex h-16 w-16 items-center justify-center rounded-2xl text-xl font-extrabold text-white shadow-lg"
                                 style="background: linear-gradient(135deg, #04395a, #068ab8);">
                                {{ step.num }}
                            </div>
                            <h3 class="mb-2 text-base font-bold text-gray-900 dark:text-white">{{ step.title }}</h3>
                            <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════
             CTA FINAL
        ══════════════════════════════════════════════════════ -->
        <section class="relative overflow-hidden py-20"
                 style="background: linear-gradient(135deg, #032f4a 0%, #04395a 60%, #065c8e 100%);">
            <div class="pointer-events-none absolute inset-0 opacity-[0.04]"
                 style="background-image: radial-gradient(circle, #68c8fb 1px, transparent 1px); background-size: 32px 32px;" />

            <div class="relative mx-auto max-w-3xl px-6 text-center">
                <h2 class="mb-4 text-3xl font-extrabold text-white sm:text-4xl">
                    Listo para comenzar
                </h2>
                <p class="mb-8 text-base" style="color: rgba(255,255,255,0.6);">
                    Accede con tu cuenta institucional y empieza a gestionar las notificaciones de manera eficiente.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-2xl px-8 py-3.5 text-base font-bold shadow-lg transition-all hover:scale-105"
                        style="background: #68c8fb; color: #04395a;"
                    >
                        Ir al Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-2xl px-8 py-3.5 text-base font-bold shadow-lg transition-all hover:scale-105"
                            style="background: #68c8fb; color: #04395a;"
                        >
                            Iniciar sesión
                        </Link>
                    </template>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════
             FOOTER
        ══════════════════════════════════════════════════════ -->
        <footer class="border-t border-gray-100 bg-white py-8 dark:border-gray-800 dark:bg-gray-950">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg"
                         style="background: #04395a;">
                        <AppLogoIcon class="size-4 fill-current" style="color: #68c8fb;" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">SisNotif</p>
                        <p class="text-xs text-gray-400">Universidad Peruana Unión</p>
                    </div>
                </div>
                <p class="text-xs text-gray-400">
                    © {{ new Date().getFullYear() }} Universidad Peruana Unión · Sistema de Notificaciones Docentes
                </p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
* {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
}
</style>
