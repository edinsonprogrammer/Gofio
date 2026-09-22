<script setup>
/**
 * Layout del panel de administración y moderación con navegación lateral
 * filtrada según permisos del rango staff o acceso total para administradores.
 */

import { Link, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import FlashNotice from '@/Components/UI/FlashNotice.vue';
import SiteBrand from '@/Components/UI/SiteBrand.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import NotificationPanel from '@/Components/Notifications/NotificationPanel.vue';

const page = usePage();

const flash = computed(() => page.props.flash ?? {});
const user = computed(() => page.props.auth.user);
const nav = computed(() => page.props.adminNav ?? []);

watch(
    () => [flash.value.success, flash.value.error],
    ([success, error]) => {
        if (success || error) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    },
);

const panelTitle = computed(() => {
    if (user.value?.is_admin) {
        return 'Panel de administración';
    }

    return 'Panel de moderación';
});

/** Determina si un enlace del menú corresponde a la ruta activa. */
const isActive = (item) => {
    if (item.exact) {
        return page.url === item.href || page.url === `${item.href}/`;
    }

    return page.url.startsWith(item.href);
};
</script>

<template>
    <div class="min-h-screen bg-backgroundPrincipal">
        <!-- Cabecera del panel con enlace de retorno al sitio público -->
        <header class="gofio-topbar text-white shadow-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2">
                <div class="flex items-center gap-3">
                    <SiteBrand href="/" variant="admin" />
                    <span class="text-sm opacity-80">{{ panelTitle }}</span>
                    <span
                        v-if="user?.rango?.nombre && !user?.is_admin"
                        class="rounded bg-white/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                    >
                        {{ user.rango.nombre }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <NotificationPanel />
                    <Link href="/" class="text-sm font-semibold hover:underline">← Volver al sitio</Link>
                </div>
            </div>
        </header>

        <div class="mx-auto grid max-w-7xl gap-4 p-4 lg:grid-cols-[220px_1fr]">
            <!-- Menú lateral filtrado por permisos del rango -->
            <aside class="gofio-box h-fit overflow-hidden">
                <div class="gofio-box-header">Herramientas</div>
                <nav class="p-2">
                    <Link
                        v-for="item in nav"
                        :key="item.key"
                        :href="item.href"
                        class="mb-0.5 flex items-center gap-2 rounded px-3 py-2 text-sm font-medium transition"
                        :class="isActive(item)
                            ? 'bg-brandColor text-white'
                            : 'text-fb-link gofio-hover-panel'"
                    >
                        <FaIcon :icon="item.icon" class="w-4 shrink-0 text-center text-xs" />
                        {{ item.label }}
                    </Link>
                    <p v-if="!nav.length" class="px-3 py-4 text-xs text-fb-muted">
                        Tu rango no tiene pestañas asignadas. Contacta a un administrador.
                    </p>
                </nav>
            </aside>

            <!-- Área de contenido con avisos flash y slot de página -->
            <main class="min-w-0 space-y-3">
                <FlashNotice v-if="flash.success" :message="flash.success" type="success" />
                <FlashNotice v-if="flash.error" :message="flash.error" type="error" />
                <slot />
            </main>
        </div>
    </div>
</template>
