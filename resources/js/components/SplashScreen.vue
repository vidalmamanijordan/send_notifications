<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

const visible = ref(false);
const hiding = ref(false);
const animateIn = ref(false);

const verses = [
    // Salmos
    { text: 'Jehová es mi pastor; nada me faltará.', reference: 'Salmos 23:1', category: 'Salmos' },
    { text: 'El Señor es mi luz y mi salvación; ¿a quién temeré?', reference: 'Salmos 27:1', category: 'Salmos' },
    { text: 'Encomienda a Jehová tu camino, y confía en él; y él hará.', reference: 'Salmos 37:5', category: 'Salmos' },
    { text: 'El que habita al abrigo del Altísimo morará bajo la sombra del Omnipotente.', reference: 'Salmos 91:1', category: 'Salmos' },
    { text: 'Sean gratos los dichos de mi boca y la meditación de mi corazón delante de ti, oh Jehová.', reference: 'Salmos 19:14', category: 'Salmos' },
    { text: 'Alma mía, en Dios solamente reposa, porque de él es mi esperanza.', reference: 'Salmos 62:5', category: 'Salmos' },
    { text: 'Gustad y ved que es bueno Jehová; dichoso el hombre que confía en él.', reference: 'Salmos 34:8', category: 'Salmos' },

    // Proverbios
    { text: 'Fíate de Jehová de todo tu corazón, y no te apoyes en tu propia prudencia.', reference: 'Proverbios 3:5', category: 'Proverbios' },
    { text: 'El corazón del hombre piensa su camino; mas Jehová endereza sus pasos.', reference: 'Proverbios 16:9', category: 'Proverbios' },
    { text: 'La esperanza que se demora enferma el corazón; pero árbol de vida es el deseo cumplido.', reference: 'Proverbios 13:12', category: 'Proverbios' },
    { text: 'Mejor es un bocado seco y en paz, que casa de contiendas llena de provisiones.', reference: 'Proverbios 17:1', category: 'Proverbios' },
    { text: 'El principio de la sabiduría es el temor de Jehová; los insensatos desprecian la sabiduría.', reference: 'Proverbios 1:7', category: 'Proverbios' },
    { text: 'Como ciudad derribada y sin muro, así es el hombre cuyo espíritu no tiene rienda.', reference: 'Proverbios 25:28', category: 'Proverbios' },
    { text: 'El hombre bueno dejará herederos a los hijos de sus hijos.', reference: 'Proverbios 13:22', category: 'Proverbios' },

    // Eclesiastés
    { text: 'Todo tiene su tiempo, y todo lo que se quiere debajo del cielo tiene su hora.', reference: 'Eclesiastés 3:1', category: 'Eclesiastés' },
    { text: 'El fin de todo el discurso oído es este: teme a Dios y guarda sus mandamientos.', reference: 'Eclesiastés 12:13', category: 'Eclesiastés' },
    { text: 'Mejor son dos que uno, porque tienen mejor paga de su trabajo.', reference: 'Eclesiastés 4:9', category: 'Eclesiastés' },
    { text: 'No hay nada mejor para el hombre que comer y beber, y que su alma se alegre en su trabajo.', reference: 'Eclesiastés 2:24', category: 'Eclesiastés' },

    // Cantares
    { text: 'Yo soy de mi amado, y mi amado es mío.', reference: 'Cantares 6:3', category: 'Cantares' },
    { text: 'Ponme como un sello sobre tu corazón, porque fuerte es como la muerte el amor.', reference: 'Cantares 8:6', category: 'Cantares' },
    { text: 'Su izquierda esté debajo de mi cabeza, y su derecha me abrace.', reference: 'Cantares 2:6', category: 'Cantares' },

    // Profetas y Evangelios
    { text: 'No temas, porque yo estoy contigo; no desmayes, porque yo soy tu Dios.', reference: 'Isaías 41:10', category: 'Isaías' },
    { text: 'Porque yo sé los pensamientos que tengo acerca de vosotros, dice Jehová, pensamientos de paz y no de mal.', reference: 'Jeremías 29:11', category: 'Jeremías' },
    { text: 'Todo lo puedo en Cristo que me fortalece.', reference: 'Filipenses 4:13', category: 'Filipenses' },
    { text: 'Mas buscad primeramente el reino de Dios y su justicia, y todas estas cosas os serán añadidas.', reference: 'Mateo 6:33', category: 'Mateo' },
    { text: 'Venid a mí todos los que estáis trabajados y cargados, y yo os haré descansar.', reference: 'Mateo 11:28', category: 'Mateo' },
];

const categoryColors: Record<string, string> = {
    'Salmos': 'text-sky-300',
    'Proverbios': 'text-amber-300',
    'Eclesiastés': 'text-emerald-300',
    'Cantares': 'text-rose-300',
    'Isaías': 'text-violet-300',
    'Jeremías': 'text-orange-300',
    'Filipenses': 'text-cyan-300',
    'Mateo': 'text-teal-300',
};

const verse = verses[Math.floor(Math.random() * verses.length)];
const accentColor = categoryColors[verse.category] ?? 'text-cyan-300';
const accentBg = accentColor.replace('text-', 'bg-');

// Palabras del versículo para animación escalonada
const words = computed(() => verse.text.split(' '));
const wordDelay = (i: number): string => {
    const maxDelay = 1400;
    const step = Math.min(80, maxDelay / words.value.length);
    return `${600 + i * step}ms`;
};

const DISPLAY_MS = 4500;
const FADE_MS = 900;

onMounted(() => {
    if (!sessionStorage.getItem('just_logged_in')) return;

    sessionStorage.removeItem('just_logged_in');
    visible.value = true;

    setTimeout(() => { animateIn.value = true; }, 50);

    setTimeout(() => {
        hiding.value = true;
        setTimeout(() => { visible.value = false; }, FADE_MS);
    }, DISPLAY_MS);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="fixed inset-0 z-[9999] flex flex-col items-center justify-center overflow-hidden"
            :style="{
                background: 'linear-gradient(135deg, #0a2e44 0%, #0c4a6e 50%, #0e3d5a 100%)',
                opacity: hiding ? 0 : 1,
                transition: `opacity ${FADE_MS}ms ease`,
            }"
        >
            <!-- Orbes de fondo -->
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="orb-drift absolute top-10 left-10 w-48 h-48 rounded-full bg-cyan-400/10 blur-3xl"></div>
                <div class="orb-drift-reverse absolute bottom-10 right-10 w-64 h-64 rounded-full bg-sky-500/10 blur-3xl"></div>
                <div class="orb-drift absolute top-1/2 left-1/3 w-36 h-36 rounded-full bg-teal-300/8 blur-2xl"></div>
            </div>

            <!-- Contenido principal -->
            <div
                class="relative flex flex-col items-center text-center px-10 max-w-2xl"
                :style="{
                    opacity: animateIn ? 1 : 0,
                    transform: animateIn ? 'translateY(0)' : 'translateY(24px)',
                    transition: 'opacity 0.8s ease, transform 0.8s ease',
                }"
            >
                <!-- Ícono con anillo giratorio -->
                <div class="relative mb-8 flex items-center justify-center">
                    <!-- Anillo exterior giratorio -->
                    <div class="ring-spin absolute w-24 h-24 rounded-full"
                        style="border: 1.5px solid transparent; border-top-color: rgba(103,232,249,0.7); border-right-color: rgba(103,232,249,0.15);">
                    </div>
                    <!-- Anillo interior contra-giratorio -->
                    <div class="ring-spin-reverse absolute w-[4.5rem] h-[4.5rem] rounded-full"
                        style="border: 1px solid transparent; border-bottom-color: rgba(103,232,249,0.4); border-left-color: rgba(103,232,249,0.1);">
                    </div>
                    <!-- Ícono flotante -->
                    <img
                        src="/favicon.png"
                        alt="NotifiK"
                        class="icon-float relative w-12 h-12"
                        :style="{ filter: 'drop-shadow(0 0 14px rgba(103,232,249,0.55))' }"
                    />
                </div>

                <!-- Categoría -->
                <span
                    class="mb-5 text-xs font-bold tracking-[0.3em] uppercase px-4 py-1 rounded-full border border-current/40"
                    :class="accentColor"
                    :style="{ opacity: 0.8 }"
                >
                    {{ verse.category }}
                </span>

                <!-- Comilla decorativa -->
                <span class="text-7xl leading-none text-white/15 font-serif -mb-3 select-none">"</span>

                <!-- Versículo palabra a palabra -->
                <p class="text-white text-lg sm:text-xl font-light italic leading-relaxed">
                    <span
                        v-for="(word, i) in words"
                        :key="i"
                        class="word-rise inline-block mr-[0.3em]"
                        :style="{ animationDelay: wordDelay(i) }"
                    >{{ word }}</span>
                </p>

                <!-- Referencia -->
                <p
                    class="mt-6 text-sm font-semibold tracking-widest uppercase ref-fade"
                    :class="accentColor"
                >
                    — {{ verse.reference }}
                </p>

                <!-- Barra de progreso -->
                <div class="mt-10 w-48 h-px bg-white/10 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full"
                        :class="accentBg"
                        :style="{
                            width: animateIn ? '100%' : '0%',
                            transition: `width ${DISPLAY_MS}ms linear`,
                        }"
                    ></div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Ícono flotando */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-10px); }
}
.icon-float {
    animation: float 3.2s ease-in-out infinite;
}

/* Anillo exterior girando */
@keyframes spin-cw {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.ring-spin {
    animation: spin-cw 4s linear infinite;
}

/* Anillo interior contra-girando */
@keyframes spin-ccw {
    from { transform: rotate(0deg); }
    to   { transform: rotate(-360deg); }
}
.ring-spin-reverse {
    animation: spin-ccw 6s linear infinite;
}

/* Palabras apareciendo en cascada */
@keyframes word-rise {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
.word-rise {
    opacity: 0;
    animation: word-rise 0.45s ease forwards;
}

/* Referencia aparece al final */
.ref-fade {
    animation: word-rise 0.6s ease forwards;
    animation-delay: 2200ms;
    opacity: 0;
}

/* Orbes moviéndose levemente */
@keyframes orb-drift {
    0%, 100% { transform: translate(0, 0); }
    50%       { transform: translate(12px, -14px); }
}
@keyframes orb-drift-reverse {
    0%, 100% { transform: translate(0, 0); }
    50%       { transform: translate(-10px, 12px); }
}
.orb-drift         { animation: orb-drift 8s ease-in-out infinite; }
.orb-drift-reverse { animation: orb-drift-reverse 10s ease-in-out infinite; }
</style>
