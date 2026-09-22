<script setup>
/**
 * Marca del sitio: muestra logo subido desde admin o el wordmark Gofio! por defecto.
 */

import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    href: {
        type: String,
        default: '/',
    },
    variant: {
        type: String,
        default: 'header',
        validator: (value) => ['header', 'login-header', 'login-lg', 'maintenance', 'admin'].includes(value),
    },
});

const page = usePage();

const siteTitle = computed(() => page.props.site?.site_title || page.props.appName || 'Gofio');

/**
 * Prioridad: logo del tema activo > logo del sitio configurado en admin.
 * El logo del tema se expone como data-gofio-logo en <html> por useGofioTheme.js.
 */
const logoUrl = computed(() => {
    const themeLogo = page.props.theme?.logo_url || '';
    const siteLogo = page.props.site?.site_logo_url || '';
    return (themeLogo || siteLogo).trim();
});
const hasLogo = computed(() => logoUrl.value.length > 0);

const linkClass = computed(() => {
    const classes = ['site-brand', `site-brand--${props.variant}`];

    if (hasLogo.value) {
        classes.push('site-brand--has-logo');
    }

    if (props.variant === 'header') {
        classes.push('gofio-topbar-brand', 'gofio-wordmark', 'gofio-wordmark-header', 'shrink-0', 'text-white', 'hover:opacity-90');
    } else if (props.variant === 'login-header') {
        classes.push('login-header-wordmark', 'text-white');
    } else if (props.variant === 'login-lg') {
        classes.push('taringa-wordmark', 'taringa-wordmark-lg', 'text-white');
    } else if (props.variant === 'maintenance') {
        classes.push('maintenance-page__brand', 'gofio-wordmark');
    } else if (props.variant === 'admin') {
        classes.push('text-lg', 'font-bold', 'hover:opacity-90');
    }

    return classes;
});
</script>

<template>
    <Link :href="href" :class="linkClass" :aria-label="`${siteTitle} - Inicio`">
        <img
            v-if="hasLogo"
            :src="logoUrl"
            :alt="siteTitle"
            class="site-logo"
            :class="`site-logo--${variant}`"
        />

        <template v-else-if="variant === 'header'">
            <span class="gofio-wordmark__full">
                <span class="gofio-wordmark__g">G</span><span class="gofio-wordmark__rest">ofio</span><span class="bang">!</span>
            </span>
            <span class="gofio-wordmark__compact" aria-hidden="true">
                <span class="gofio-wordmark__g">G</span><span class="bang">!</span>
            </span>
        </template>

        <template v-else-if="variant === 'admin'">
            {{ siteTitle }}
        </template>

        <template v-else>
            <span class="gofio-wordmark__g">G</span><span class="gofio-wordmark__rest">ofio</span><span class="bang">!</span>
        </template>
    </Link>
</template>
