/**
 * Punto de entrada de la aplicación SPA: monta Inertia con Vue 3,
 * resuelve páginas dinámicamente y sincroniza tema y token CSRF tras cada navegación.
 */

import './bootstrap';
import './echo';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { applyGofioTheme } from './composables/useGofioTheme';
import { syncCsrfToken } from './bootstrap';

/** Nombre de la aplicación tomado de la variable de entorno Vite o valor por defecto. */
const appName = import.meta.env.VITE_APP_NAME || 'Gofio';

createInertiaApp({
    title: (title) => (title ? `${title} | ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Aplica el tema activo (colores, layout y logo) antes del primer render.
        if (props.initialPage?.props?.theme) {
            const theme = props.initialPage.props.theme;
            applyGofioTheme(theme.variables, theme.slug, theme.custom_css, theme.layout_variant, theme.logo_url);
        }

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#3B5998',
    },
});

// Tras cada visita exitosa, reaplica tema y actualiza el meta CSRF para peticiones posteriores.
router.on('success', (event) => {
    const theme = event.detail.page.props.theme;
    if (theme?.variables) {
        // Reaplica todo el tema tras navegación: variables, layout variant y logo
        applyGofioTheme(theme.variables, theme.slug, theme.custom_css, theme.layout_variant, theme.logo_url);
    }

    if (event.detail.page.props.csrf_token) {
        syncCsrfToken(event.detail.page.props.csrf_token);
    }
});

