import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';

// ✅ ZIGGY
import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy';

// ✅ TOASTR
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// 🔔 Configuración global de Toastr
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    timeOut: 4000,
    extendedTimeOut: 1000,
    showDuration: 300,
    hideDuration: 300,
};

// 👉 Hacer toastr global
declare global {
    interface Window {
        toastr: typeof toastr;
    }
}
window.toastr = toastr;

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy as any)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
