<script setup lang="ts">
import { dashboard, login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import notifikaLogo from '../../images/siderbar/notifika_logo_oficial.svg';
import notifikaIcon from '../../images/welcome/notifik_circle.svg';
import asuntosAcademicosLogo from '../../images/welcome/asuntos_academicos.svg';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const slides = [
    {
        tag: 'Automatización inteligente',
        title: { before: 'Gestión inteligente de ', highlight: 'notificaciones', after: ' para docentes' },
        desc: 'Automatiza el envío de correos sobre rubros vencidos, evaluaciones y avisos académicos con plantillas personalizables.',
        icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        color: '#68c8fb',
    },
    {
        tag: 'Importación de datos',
        title: { before: 'Carga reportes ', highlight: 'Excel', after: ' en segundos' },
        desc: 'Importa el archivo de evaluaciones docentes y el sistema detecta automáticamente todos los rubros vencidos del periodo.',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        color: '#10b981',
    },
    {
        tag: 'Envío masivo',
        title: { before: 'Lotes de ', highlight: 'notificaciones', after: ' con un clic' },
        desc: 'Genera y envía correos masivos a todos los docentes con observaciones pendientes. Controla, pausa y reenvía desde el dashboard.',
        icon: 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
        color: '#f59e0b',
    },
    {
        tag: 'Seguimiento en tiempo real',
        title: { before: 'Monitorea cada ', highlight: 'notificación', after: ' enviada' },
        desc: 'Visualiza el estado de entrega de cada correo, detecta fallos y accede al historial completo por docente y periodo académico.',
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        color: '#8b5cf6',
    },
    {
        tag: 'Multi-campus',
        title: { before: 'Cobertura ', highlight: 'total', after: ' en todos los campus' },
        desc: 'Administra docentes, facultades, programas y periodos académicos de todos los campus de la Universidad Peruana Unión.',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        color: '#ef4444',
    },
];

const current = ref(0);
const animating = ref(false);
let timer: ReturnType<typeof setInterval>;

function goTo(index: number): void {
    if (animating.value || index === current.value) return;
    animating.value = true;
    setTimeout(() => {
        current.value = index;
        animating.value = false;
    }, 350);
}

function next(): void {
    goTo((current.value + 1) % slides.length);
}

function startTimer(): void {
    timer = setInterval(next, 4500);
}

function resetTimer(): void {
    clearInterval(timer);
    startTimer();
}

function hexToRgb(hex: string): string {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return `${r},${g},${b}`;
}

onMounted(() => startTimer());
onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <Head title="NotifiK — Sistema de Notificaciones">
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
                    <img :src="notifikaLogo" alt="NotifiK" class="h-9 w-auto object-contain" />
                    <div>
                        <p class="text-sm font-bold leading-none text-white">Notifi<span class="font-black text-yellow-400" style="font-size:1.1em;">K</span></p>
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
            </div>

            <div class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-24 lg:grid-cols-2 lg:py-32">

                <!-- ── COLUMNA IZQUIERDA: Icono animado ── -->
                <div class="flex flex-col items-center justify-center">

                    <!-- Icono con anillos animados -->
                    <div class="relative flex items-center justify-center">
                        <!-- Anillo más exterior (muy sutil) -->
                        <div class="hero-ring-xl absolute h-72 w-72 rounded-full"
                             style="border: 1px solid rgba(104,200,251,0.08);" />
                        <!-- Anillo exterior giratorio -->
                        <div class="hero-ring-outer absolute h-52 w-52 rounded-full"
                             style="border: 1.5px solid transparent; border-top-color: rgba(104,200,251,0.6); border-right-color: rgba(104,200,251,0.15);" />
                        <!-- Anillo medio contra-giratorio -->
                        <div class="hero-ring-inner absolute h-36 w-36 rounded-full"
                             style="border: 1px solid transparent; border-bottom-color: rgba(104,200,251,0.4); border-left-color: rgba(104,200,251,0.1);" />
                        <!-- Glow de fondo -->
                        <div class="absolute h-28 w-28 rounded-full blur-2xl"
                             style="background: rgba(104,200,251,0.15);" />
                        <!-- Icono flotante -->
                        <div class="hero-float relative z-10 flex h-24 w-24 items-center justify-center">
                            <img
                                :src="notifikaIcon"
                                alt="NotifiK"
                                class="h-full w-full object-contain"
                                style="filter: drop-shadow(0 0 20px rgba(104,200,251,0.6));"
                            />
                        </div>
                    </div>

                    <!-- Nombre del sistema -->
                    <div class="mt-10 text-center">
                        <h2 class="hero-shimmer text-3xl font-black tracking-[0.2em] uppercase select-none">
                            NotifiK
                        </h2>
                        <p class="mt-2 text-xs tracking-widest uppercase select-none"
                           style="color: rgba(104,200,251,0.5);">
                            Universidad Peruana Unión
                        </p>
                    </div>

                    <!-- CTA -->
                    <div class="mt-10 relative">
                        <!-- Efecto de palpitación (onda expansiva) -->
                        <span class="btn-pulse absolute inset-0 rounded-2xl" style="background: #68c8fb;" />
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="btn-hero relative flex items-center gap-2 rounded-2xl px-7 py-3.5 text-base font-bold shadow-lg"
                            style="background: #68c8fb; color: #04395a;"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Ir al Dashboard
                        </Link>
                        <Link
                            v-else
                            :href="login()"
                            class="btn-hero relative flex items-center gap-2 rounded-2xl px-7 py-3.5 text-base font-bold shadow-lg"
                            style="background: #68c8fb; color: #04395a;"
                        >
                            Iniciar sesión
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- ── COLUMNA DERECHA: Carrusel ── -->
                <div class="flex flex-col justify-center">
                    <div class="relative overflow-hidden rounded-3xl p-8 flex flex-col justify-between"
                         style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(12px); min-height: 360px;">

                        <!-- Slide -->
                        <div
                            class="carousel-slide flex-1 flex flex-row items-center gap-5"
                            :class="animating ? 'carousel-out' : 'carousel-in'"
                        >
                            <!-- Texto (izquierda) -->
                            <div class="flex-1 flex flex-col justify-center min-w-0">
                                <!-- Tag -->
                                <span class="mb-4 inline-flex w-fit items-center gap-2 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-widest"
                                      :style="`background: rgba(${hexToRgb(slides[current].color)}, 0.12); color: ${slides[current].color}; border: 1px solid rgba(${hexToRgb(slides[current].color)}, 0.25);`">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="slides[current].icon" />
                                    </svg>
                                    {{ slides[current].tag }}
                                </span>
                                <!-- Título -->
                                <h1 class="mb-3 text-2xl font-extrabold leading-tight tracking-tight text-white sm:text-3xl">
                                    {{ slides[current].title.before }}<span :style="`color: ${slides[current].color}`">{{ slides[current].title.highlight }}</span>{{ slides[current].title.after }}
                                </h1>
                                <!-- Descripción -->
                                <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.6);">
                                    {{ slides[current].desc }}
                                </p>
                            </div>

                            <!-- Ilustración SVG (derecha) -->
                            <div class="shrink-0 overflow-hidden rounded-2xl flex items-center justify-center w-40 h-40"
                                 :style="`background: rgba(${hexToRgb(slides[current].color)}, 0.05); border: 1px solid rgba(${hexToRgb(slides[current].color)}, 0.12);`">

                                <!-- Slide 0: Notificaciones por email -->
                                <svg v-if="current === 0" viewBox="0 0 280 130" fill="none" class="w-full h-full">
                                    <rect x="55" y="35" width="170" height="75" rx="10" fill="rgba(104,200,251,0.08)" stroke="rgba(104,200,251,0.3)" stroke-width="1.5"/>
                                    <path d="M55 48 L140 88 L225 48" stroke="rgba(104,200,251,0.45)" stroke-width="1.5" fill="none"/>
                                    <line x1="82" y1="72" x2="155" y2="72" stroke="rgba(104,200,251,0.18)" stroke-width="2" stroke-linecap="round"/>
                                    <line x1="82" y1="82" x2="135" y2="82" stroke="rgba(104,200,251,0.12)" stroke-width="2" stroke-linecap="round"/>
                                    <circle cx="213" cy="28" r="22" fill="rgba(104,200,251,0.08)" stroke="rgba(104,200,251,0.25)" stroke-width="1"/>
                                    <path d="M205 26 C205 21 209 17 213 17 C217 17 221 21 221 26 L223 35 L203 35 Z" fill="rgba(104,200,251,0.45)" stroke="rgba(104,200,251,0.6)" stroke-width="1"/>
                                    <line x1="203" y1="35" x2="223" y2="35" stroke="rgba(104,200,251,0.75)" stroke-width="1.5" stroke-linecap="round"/>
                                    <circle cx="213" cy="38" r="2.5" fill="rgba(104,200,251,0.85)"/>
                                    <circle cx="225" cy="14" r="8" fill="#68c8fb"/>
                                    <text x="225" y="18.5" text-anchor="middle" fill="#04395a" font-size="9" font-weight="bold">3</text>
                                    <circle cx="33" cy="43" r="4" fill="rgba(104,200,251,0.25)"/>
                                    <circle cx="23" cy="68" r="2.5" fill="rgba(104,200,251,0.18)"/>
                                    <circle cx="248" cy="78" r="3" fill="rgba(104,200,251,0.2)"/>
                                    <rect x="18" y="58" width="28" height="20" rx="4" fill="rgba(104,200,251,0.04)" stroke="rgba(104,200,251,0.15)" stroke-width="1"/>
                                    <path d="M18 64 L32 72 L46 64" stroke="rgba(104,200,251,0.18)" stroke-width="1" fill="none"/>
                                </svg>

                                <!-- Slide 1: Importación Excel -->
                                <svg v-else-if="current === 1" viewBox="0 0 280 130" fill="none" class="w-full h-full">
                                    <rect x="80" y="12" width="115" height="105" rx="8" fill="rgba(16,185,129,0.07)" stroke="rgba(16,185,129,0.3)" stroke-width="1.5"/>
                                    <path d="M162 12 L195 12 L162 45 Z" fill="rgba(16,185,129,0.15)" stroke="rgba(16,185,129,0.3)" stroke-width="1"/>
                                    <rect x="91" y="52" width="93" height="12" rx="2" fill="rgba(16,185,129,0.2)"/>
                                    <line x1="91" y1="72" x2="184" y2="72" stroke="rgba(16,185,129,0.18)" stroke-width="1"/>
                                    <line x1="91" y1="84" x2="184" y2="84" stroke="rgba(16,185,129,0.18)" stroke-width="1"/>
                                    <line x1="91" y1="96" x2="184" y2="96" stroke="rgba(16,185,129,0.18)" stroke-width="1"/>
                                    <line x1="121" y1="52" x2="121" y2="108" stroke="rgba(16,185,129,0.18)" stroke-width="1"/>
                                    <line x1="153" y1="52" x2="153" y2="108" stroke="rgba(16,185,129,0.18)" stroke-width="1"/>
                                    <rect x="92" y="65" width="28" height="6" rx="1" fill="rgba(16,185,129,0.18)"/>
                                    <rect x="92" y="77" width="20" height="6" rx="1" fill="rgba(16,185,129,0.12)"/>
                                    <rect x="122" y="65" width="30" height="6" rx="1" fill="rgba(16,185,129,0.14)"/>
                                    <rect x="154" y="65" width="29" height="6" rx="1" fill="rgba(16,185,129,0.1)"/>
                                    <circle cx="38" cy="65" r="22" fill="rgba(16,185,129,0.08)" stroke="rgba(16,185,129,0.3)" stroke-width="1.5"/>
                                    <path d="M38 76 L38 54 M31 61 L38 54 L45 61" stroke="#10b981" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M60 65 L80 65" stroke="rgba(16,185,129,0.4)" stroke-width="1.5" stroke-dasharray="3 2" stroke-linecap="round"/>
                                    <circle cx="232" cy="35" r="16" fill="rgba(16,185,129,0.12)" stroke="rgba(16,185,129,0.4)" stroke-width="1.5"/>
                                    <path d="M224 35 L230 41 L241 28" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>

                                <!-- Slide 2: Envío masivo -->
                                <svg v-else-if="current === 2" viewBox="0 0 280 130" fill="none" class="w-full h-full">
                                    <rect x="48" y="52" width="112" height="68" rx="8" fill="rgba(245,158,11,0.04)" stroke="rgba(245,158,11,0.18)" stroke-width="1" transform="rotate(-7 104 86)"/>
                                    <rect x="48" y="47" width="112" height="68" rx="8" fill="rgba(245,158,11,0.07)" stroke="rgba(245,158,11,0.25)" stroke-width="1" transform="rotate(-3 104 81)"/>
                                    <rect x="48" y="42" width="112" height="68" rx="8" fill="rgba(245,158,11,0.11)" stroke="rgba(245,158,11,0.4)" stroke-width="1.5"/>
                                    <path d="M48 55 L104 84 L160 55" stroke="rgba(245,158,11,0.5)" stroke-width="1.5" fill="none"/>
                                    <rect x="178" y="16" width="52" height="36" rx="5" fill="rgba(245,158,11,0.09)" stroke="rgba(245,158,11,0.32)" stroke-width="1.2" transform="rotate(14 204 34)"/>
                                    <path d="M178 23 L204 37 L230 23" stroke="rgba(245,158,11,0.38)" stroke-width="1" fill="none" transform="rotate(14 204 34)"/>
                                    <rect x="185" y="70" width="50" height="34" rx="5" fill="rgba(245,158,11,0.07)" stroke="rgba(245,158,11,0.28)" stroke-width="1.2" transform="rotate(-10 210 87)"/>
                                    <path d="M185 77 L210 90 L235 77" stroke="rgba(245,158,11,0.32)" stroke-width="1" fill="none" transform="rotate(-10 210 87)"/>
                                    <path d="M160 60 L177 44" stroke="rgba(245,158,11,0.3)" stroke-width="1" stroke-dasharray="4 3" stroke-linecap="round"/>
                                    <path d="M160 70 L183 80" stroke="rgba(245,158,11,0.25)" stroke-width="1" stroke-dasharray="4 3" stroke-linecap="round"/>
                                    <circle cx="248" cy="58" r="16" fill="rgba(245,158,11,0.13)" stroke="rgba(245,158,11,0.4)" stroke-width="1.5"/>
                                    <path d="M240 58 L256 58 M249 51 L256 58 L249 65" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="22" cy="52" r="3.5" fill="rgba(245,158,11,0.28)"/>
                                    <circle cx="28" cy="76" r="2.2" fill="rgba(245,158,11,0.2)"/>
                                </svg>

                                <!-- Slide 3: Seguimiento tiempo real -->
                                <svg v-else-if="current === 3" viewBox="0 0 280 130" fill="none" class="w-full h-full">
                                    <rect x="22" y="18" width="185" height="98" rx="10" fill="rgba(139,92,246,0.06)" stroke="rgba(139,92,246,0.2)" stroke-width="1.5"/>
                                    <line x1="38" y1="38" x2="192" y2="38" stroke="rgba(139,92,246,0.1)" stroke-width="1"/>
                                    <line x1="38" y1="58" x2="192" y2="58" stroke="rgba(139,92,246,0.1)" stroke-width="1"/>
                                    <line x1="38" y1="78" x2="192" y2="78" stroke="rgba(139,92,246,0.1)" stroke-width="1"/>
                                    <line x1="38" y1="98" x2="192" y2="98" stroke="rgba(139,92,246,0.1)" stroke-width="1"/>
                                    <path d="M38 100 L72 88 L102 76 L130 58 L158 42 L182 28" stroke="#8b5cf6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                    <path d="M38 100 L72 88 L102 76 L130 58 L158 42 L182 28 L182 100 L38 100 Z" fill="rgba(139,92,246,0.07)"/>
                                    <circle cx="72"  cy="88" r="3.5" fill="#8b5cf6"/>
                                    <circle cx="102" cy="76" r="3.5" fill="#8b5cf6"/>
                                    <circle cx="130" cy="58" r="3.5" fill="#8b5cf6"/>
                                    <circle cx="158" cy="42" r="3.5" fill="#8b5cf6"/>
                                    <circle cx="182" cy="28" r="5.5" fill="#8b5cf6" stroke="rgba(139,92,246,0.35)" stroke-width="4"/>
                                    <circle cx="182" cy="28" r="11" stroke="rgba(139,92,246,0.25)" stroke-width="1.5" fill="none"/>
                                    <circle cx="240" cy="35" r="17" fill="rgba(139,92,246,0.1)" stroke="rgba(139,92,246,0.3)" stroke-width="1.5"/>
                                    <path d="M232 35 L238 41 L249 27" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="240" cy="72" r="12" fill="rgba(139,92,246,0.07)" stroke="rgba(139,92,246,0.22)" stroke-width="1"/>
                                    <path d="M234 72 L239 77 L247 65" stroke="rgba(139,92,246,0.65)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="240" cy="102" r="10" fill="rgba(139,92,246,0.05)" stroke="rgba(139,92,246,0.18)" stroke-width="1"/>
                                    <path d="M235 102 L239 106 L246 96" stroke="rgba(139,92,246,0.45)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>

                                <!-- Slide 4: Multi-campus -->
                                <svg v-else viewBox="0 0 280 130" fill="none" class="w-full h-full">
                                    <rect x="105" y="42" width="70" height="78" rx="4" fill="rgba(239,68,68,0.09)" stroke="rgba(239,68,68,0.35)" stroke-width="1.5"/>
                                    <rect x="115" y="52" width="12" height="10" rx="2" fill="rgba(239,68,68,0.25)"/>
                                    <rect x="133" y="52" width="12" height="10" rx="2" fill="rgba(239,68,68,0.25)"/>
                                    <rect x="151" y="52" width="12" height="10" rx="2" fill="rgba(239,68,68,0.25)"/>
                                    <rect x="115" y="69" width="12" height="10" rx="2" fill="rgba(239,68,68,0.18)"/>
                                    <rect x="133" y="69" width="12" height="10" rx="2" fill="rgba(239,68,68,0.18)"/>
                                    <rect x="151" y="69" width="12" height="10" rx="2" fill="rgba(239,68,68,0.18)"/>
                                    <rect x="130" y="98" width="20" height="22" rx="2" fill="rgba(239,68,68,0.28)"/>
                                    <path d="M99 42 L140 16 L181 42" fill="rgba(239,68,68,0.07)" stroke="rgba(239,68,68,0.28)" stroke-width="1.5"/>
                                    <rect x="28" y="58" width="55" height="62" rx="4" fill="rgba(239,68,68,0.06)" stroke="rgba(239,68,68,0.22)" stroke-width="1"/>
                                    <rect x="38" y="68" width="10" height="8" rx="1" fill="rgba(239,68,68,0.2)"/>
                                    <rect x="55" y="68" width="10" height="8" rx="1" fill="rgba(239,68,68,0.2)"/>
                                    <rect x="38" y="83" width="10" height="8" rx="1" fill="rgba(239,68,68,0.14)"/>
                                    <rect x="55" y="83" width="10" height="8" rx="1" fill="rgba(239,68,68,0.14)"/>
                                    <rect x="43" y="98" width="14" height="22" rx="1" fill="rgba(239,68,68,0.22)"/>
                                    <rect x="197" y="58" width="55" height="62" rx="4" fill="rgba(239,68,68,0.06)" stroke="rgba(239,68,68,0.22)" stroke-width="1"/>
                                    <rect x="207" y="68" width="10" height="8" rx="1" fill="rgba(239,68,68,0.2)"/>
                                    <rect x="224" y="68" width="10" height="8" rx="1" fill="rgba(239,68,68,0.2)"/>
                                    <rect x="207" y="83" width="10" height="8" rx="1" fill="rgba(239,68,68,0.14)"/>
                                    <rect x="224" y="83" width="10" height="8" rx="1" fill="rgba(239,68,68,0.14)"/>
                                    <rect x="212" y="98" width="14" height="22" rx="1" fill="rgba(239,68,68,0.22)"/>
                                    <path d="M83 75 L105 65" stroke="rgba(239,68,68,0.28)" stroke-width="1" stroke-dasharray="4 3"/>
                                    <path d="M175 65 L197 75" stroke="rgba(239,68,68,0.28)" stroke-width="1" stroke-dasharray="4 3"/>
                                    <circle cx="55" cy="50" r="7" fill="rgba(239,68,68,0.18)" stroke="rgba(239,68,68,0.4)" stroke-width="1"/>
                                    <circle cx="55" cy="50" r="3" fill="#ef4444"/>
                                    <path d="M55 57 L55 62" stroke="rgba(239,68,68,0.4)" stroke-width="1.5" stroke-linecap="round"/>
                                    <circle cx="224" cy="50" r="7" fill="rgba(239,68,68,0.18)" stroke="rgba(239,68,68,0.4)" stroke-width="1"/>
                                    <circle cx="224" cy="50" r="3" fill="#ef4444"/>
                                    <path d="M224 57 L224 62" stroke="rgba(239,68,68,0.4)" stroke-width="1.5" stroke-linecap="round"/>
                                    <circle cx="140" cy="8" r="8" fill="rgba(239,68,68,0.22)" stroke="rgba(239,68,68,0.5)" stroke-width="1.5"/>
                                    <circle cx="140" cy="8" r="3.5" fill="#ef4444"/>
                                    <path d="M140 16 L140 22" stroke="rgba(239,68,68,0.5)" stroke-width="1.5" stroke-linecap="round"/>
                                    <line x1="18" y1="120" x2="262" y2="120" stroke="rgba(239,68,68,0.14)" stroke-width="1"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Controles del carrusel -->
                        <div class="mt-8 flex items-center justify-between">
                            <!-- Dots -->
                            <div class="flex items-center gap-2">
                                <button
                                    v-for="(_, i) in slides"
                                    :key="i"
                                    @click="() => { goTo(i); resetTimer(); }"
                                    class="rounded-full transition-all duration-300"
                                    :style="i === current
                                        ? `width:24px; height:6px; background:${slides[current].color};`
                                        : 'width:6px; height:6px; background:rgba(255,255,255,0.25);'"
                                />
                            </div>

                            <!-- Flechas -->
                            <div class="flex items-center gap-2">
                                <button
                                    @click="() => { goTo((current - 1 + slides.length) % slides.length); resetTimer(); }"
                                    class="flex h-9 w-9 items-center justify-center rounded-full transition-all hover:scale-110"
                                    style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);"
                                >
                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                                <button
                                    @click="() => { next(); resetTimer(); }"
                                    class="flex h-9 w-9 items-center justify-center rounded-full transition-all hover:scale-110"
                                    style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);"
                                >
                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Barra de progreso -->
                        <div class="mt-4 h-px w-full overflow-hidden rounded-full" style="background: rgba(255,255,255,0.08);">
                            <div
                                class="carousel-progress h-full rounded-full"
                                :style="`background: ${slides[current].color};`"
                                :key="current"
                            />
                        </div>
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
        <footer class="border-t border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-950">
            <!-- Top footer row -->
            <div class="mx-auto max-w-7xl px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-6">
                    <!-- Brand -->
                    <div class="flex items-center gap-3">
                        <img :src="notifikaLogo" alt="NotifiK" class="h-9 w-auto object-contain" />
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                Notifi<span class="font-black text-yellow-400" style="font-size:1.1em;">K</span>
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Universidad Peruana Unión</p>
                        </div>
                    </div>

                    <!-- Developed by -->
                    <div class="flex flex-col items-end gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-medium text-gray-400 dark:text-gray-500">Powered by</span>
                            <img :src="asuntosAcademicosLogo" alt="Asuntos Académicos" class="h-8 w-auto object-contain" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom copyright bar -->
            <div class="border-t border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                <div class="mx-auto max-w-7xl px-6 py-3">
                    <p class="text-center text-xs text-gray-400 dark:text-gray-600">
                        © {{ new Date().getFullYear() }} Universidad Peruana Unión · Sistema de Notificaciones Docentes
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
* {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
}

/* ── Icono animado ── */
@keyframes hero-float {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-14px); }
}
@keyframes hero-ring-cw {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
@keyframes hero-ring-ccw {
    from { transform: rotate(0deg); }
    to   { transform: rotate(-360deg); }
}
@keyframes hero-ring-xl-spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
@keyframes hero-shimmer {
    0%   { background-position: -300% center; }
    100% { background-position: 300% center; }
}

.hero-float     { animation: hero-float 3.5s ease-in-out infinite; }
.hero-ring-outer { animation: hero-ring-cw 7s linear infinite; }
.hero-ring-inner { animation: hero-ring-ccw 5s linear infinite; }
.hero-ring-xl    { animation: hero-ring-xl-spin 20s linear infinite; }

.hero-shimmer {
    background: linear-gradient(90deg, #68c8fb 0%, #ffffff 35%, #068ab8 55%, #68c8fb 100%);
    background-size: 300% auto;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: hero-shimmer 4s linear infinite;
}

/* ── Botón suave ── */
@keyframes btn-soft {
    0%, 100% { box-shadow: 0 4px 18px rgba(104,200,251,0.2); opacity: 1; }
    50%       { box-shadow: 0 4px 28px rgba(104,200,251,0.45); opacity: 0.92; }
}

.btn-pulse { display: none; }
.btn-hero {
    animation: btn-soft 3s ease-in-out infinite;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.btn-hero:hover {
    animation: none;
    transform: scale(1.04);
    box-shadow: 0 6px 28px rgba(104,200,251,0.45);
}

/* ── Carrusel ── */
@keyframes slide-in {
    from { opacity: 0; transform: translateX(24px); }
    to   { opacity: 1; transform: translateX(0); }
}
@keyframes slide-out {
    from { opacity: 1; transform: translateX(0); }
    to   { opacity: 0; transform: translateX(-24px); }
}
@keyframes progress-fill {
    from { width: 0%; }
    to   { width: 100%; }
}

.carousel-in  { animation: slide-in 0.4s ease forwards; }
.carousel-out { animation: slide-out 0.35s ease forwards; }

.carousel-progress {
    animation: progress-fill 4.5s linear forwards;
}
</style>
