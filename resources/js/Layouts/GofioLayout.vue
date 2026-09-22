<script setup>
/**
 * Layout principal de la aplicación autenticada: barra superior, sidebars
 * de categorías y cuenta, área de contenido con slots y docks flotantes.
 */

import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ChatDock from '@/Components/Chat/ChatDock.vue';
import NotificationPanel from '@/Components/Notifications/NotificationPanel.vue';
import TipNotification from '@/Components/Wallet/TipNotification.vue';
import FlashNotice from '@/Components/UI/FlashNotice.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import SiteCopyrightFooter from '@/Components/UI/SiteCopyrightFooter.vue';
import SiteBrand from '@/Components/UI/SiteBrand.vue';
import RankBadge from '@/Components/User/RankBadge.vue';
import MobileBottomNav from '@/Components/Navigation/MobileBottomNav.vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const chatDockRef = ref(null);
const searchQuery = ref('');
const searchError = ref('');

/** Usuario autenticado y flags de funcionalidades desde props compartidas de Inertia. */
const user = computed(() => page.props.auth.user);
const features = computed(() => page.props.features ?? { ads_enabled: true });
const flash = computed(() => page.props.flash ?? {});

// Desplaza al inicio cuando llega un mensaje flash de éxito o error.
watch(
    () => [flash.value.success, flash.value.error],
    ([success, error]) => {
        if (success || error) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    },
);

/** Ruta actual sin query string ni barra final. */
const currentPath = computed(() => {
    const path = page.url.split('?')[0].replace(/\/$/, '');
    return path || '/';
});

const isHome = computed(() => currentPath.value === '/');
const isSearch = computed(() => currentPath.value === '/buscar');

/** Slug de categoría activa extraído del parámetro ?categoria=. */
const selectedCategorySlug = computed(() => {
    const queryPart = page.url.includes('?') ? page.url.split('?')[1] : '';

    return new URLSearchParams(queryPart).get('categoria') || null;
});

/** Alterna el filtro de categoría en home o redirige al feed filtrado. */
const selectCategory = (slug) => {
    const nextSlug = selectedCategorySlug.value === slug ? null : slug;

    if (isHome.value) {
        router.get('/', nextSlug ? { categoria: nextSlug } : {}, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
        return;
    }

    router.get('/', nextSlug ? { categoria: nextSlug } : {});
};

/** Sincroniza el campo de búsqueda con el parámetro q de la URL. */
const syncSearchFromUrl = () => {
    if (!isSearch.value) {
        return;
    }
    const queryPart = page.url.includes('?') ? page.url.split('?')[1] : '';
    searchQuery.value = new URLSearchParams(queryPart).get('q') ?? '';
};

watch(() => page.url, syncSearchFromUrl, { immediate: true });

/** Cierra la sesión del usuario mediante POST a /logout. */
const logout = () => {
    router.post('/logout');
};

const profileUrl = computed(() => (user.value ? `/perfil/${user.value.username}` : '#'));

/** Saldo de monedas formateado con separador decimal español. */
const formattedBalance = computed(() => {
    const raw = user.value?.balance_monedas ?? 0;
    const amount = Number(raw);

    if (Number.isNaN(amount)) {
        return raw;
    }

    return amount.toLocaleString('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
});

/** Valida longitud mínima y navega a la página de resultados de búsqueda. */
const submitSearch = () => {
    const q = searchQuery.value.trim();
    searchError.value = '';

    if (q.length < 2) {
        searchError.value = 'Mínimo 2 caracteres.';
        return;
    }

    router.get('/buscar', { q }, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <div class="gofio-app-shell min-h-screen overflow-x-hidden bg-backgroundPrincipal">
        <!-- Barra superior: marca, buscador y acciones de usuario -->
        <header class="gofio-topbar sticky top-0 z-50 text-white shadow-md">
            <div class="gofio-topbar-shell mx-auto max-w-6xl px-3 py-2">
                <div class="gofio-topbar-grid">
                    <SiteBrand href="/" variant="header" />

                    <form class="gofio-topbar-search min-w-0" @submit.prevent="submitSearch">
                        <div class="gofio-topbar-search__field">
                            <FaIcon
                                icon="fa-solid fa-magnifying-glass"
                                class="gofio-topbar-search__icon"
                            />
                            <input
                                v-model="searchQuery"
                                type="search"
                                name="q"
                                placeholder="Buscar @nick o posts"
                                autocomplete="off"
                                class="gofio-topbar-search__input"
                                :class="{ 'gofio-topbar-search__input--error': searchError }"
                                @input="searchError = ''"
                            />
                        </div>
                        <p v-if="searchError" class="gofio-topbar-search__error">{{ searchError }}</p>
                    </form>

                    <nav class="gofio-topbar-nav flex items-center text-sm font-semibold">
                    <NotificationPanel />
                    <button
                        type="button"
                        class="gofio-nav-icon-btn"
                        title="Mensajes"
                        aria-label="Mensajes"
                        @click="chatDockRef?.open()"
                    >
                        <FaIcon icon="fa-solid fa-comment-dots" />
                    </button>

                    <div class="gofio-topbar-user ml-0.5 flex items-center gap-1 border-l border-white/25 pl-1.5 sm:ml-1.5 sm:pl-2.5">
                        <Link
                            :href="profileUrl"
                            class="gofio-nav-icon-btn gofio-nav-icon-btn--avatar overflow-hidden p-0"
                            :title="user?.username ?? 'Mi perfil'"
                            aria-label="Mi perfil"
                        >
                            <img
                                v-if="user?.avatar_url"
                                :src="user.avatar_url"
                                alt=""
                                class="h-full w-full object-cover"
                            />
                            <span v-else class="flex h-full w-full items-center justify-center bg-[#DADDE1] text-xs font-bold text-fb-muted">
                                {{ user?.username?.charAt(0).toUpperCase() }}
                            </span>
                        </Link>
                        <button
                            type="button"
                            class="gofio-nav-icon-btn"
                            title="Cerrar sesión"
                            aria-label="Cerrar sesión"
                            @click="logout"
                        >
                            <FaIcon icon="fa-solid fa-right-from-bracket" />
                        </button>
                    </div>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Área de contenido principal: left sidebar + feed + right sidebar -->
        <div class="gofio-main-content mx-auto grid max-w-6xl gap-4 px-3 py-4 lg:grid-cols-[200px_1fr_240px]">
            <!-- Sidebar izquierda: perfil, categorías, estadísticas y enlaces de cuenta -->
            <aside class="gofio-left-sidebar hidden space-y-3 lg:block">
                <div class="gofio-box p-3">
                    <Link :href="profileUrl" class="flex items-center gap-2 hover:underline">
                        <div class="flex h-10 w-10 items-center justify-center rounded bg-[#DADDE1] text-sm font-bold text-fb-muted">
                            {{ user?.username?.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-textPrincipal">{{ user?.username }}</p>
                            <RankBadge v-if="user?.rango" :rango="user.rango" />
                            <p v-else class="text-xs text-fb-muted">Newbie</p>
                        </div>
                    </Link>
                </div>

                <div v-if="categories.length" class="gofio-box overflow-hidden">
                    <div class="gofio-box-header">Categorías</div>
                    <ul class="p-2">
                        <li>
                            <button
                                type="button"
                                class="gofio-category-filter"
                                :class="{ 'gofio-category-filter--active': isHome && !selectedCategorySlug }"
                                @click="selectCategory(null)"
                            >
                                <FaIcon icon="fa-solid fa-house" class="gofio-category-filter__icon" />
                                Todas
                            </button>
                        </li>
                        <li v-for="cat in categories" :key="cat.id">
                            <button
                                type="button"
                                class="gofio-category-filter"
                                :class="{ 'gofio-category-filter--active': selectedCategorySlug === cat.slug }"
                                @click="selectCategory(cat.slug)"
                            >
                                <FaIcon :icon="cat.icon" class="gofio-category-filter__icon" />
                                {{ cat.name }}
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="gofio-sidebar-stats overflow-hidden">
                    <div class="gofio-sidebar-stats__header">
                        <FaIcon icon="fa-solid fa-chart-simple" class="gofio-sidebar-stats__header-icon" />
                        Estadísticas
                    </div>
                    <div class="gofio-sidebar-stats__body">
                        <div class="gofio-sidebar-stat">
                            <span class="gofio-sidebar-stat__icon gofio-sidebar-stat__icon--karma">
                                <FaIcon icon="fa-solid fa-bolt" />
                            </span>
                            <div class="gofio-sidebar-stat__content">
                                <span class="gofio-sidebar-stat__label">Karma</span>
                                <span class="gofio-sidebar-stat__value">{{ user?.karma ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="gofio-sidebar-stat">
                            <span class="gofio-sidebar-stat__icon gofio-sidebar-stat__icon--coins">
                                <FaIcon icon="fa-solid fa-coins" />
                            </span>
                            <div class="gofio-sidebar-stat__content">
                                <span class="gofio-sidebar-stat__label">Monedas</span>
                                <span class="gofio-sidebar-stat__value">{{ formattedBalance }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header">Cuenta</div>
                    <ul class="p-2 text-sm">
                        <li>
                            <Link href="/configuracion/perfil" class="block rounded px-2 py-1.5 text-fb-link gofio-hover-panel">
                                Perfil / @nick
                            </Link>
                        </li>
                        <li v-if="user?.can_customize_appearance">
                            <Link href="/configuracion/apariencia" class="block rounded px-2 py-1.5 text-fb-link gofio-hover-panel">
                                Apariencia
                            </Link>
                        </li>
                        <li>
                            <Link href="/configuracion/verificacion" class="block rounded px-2 py-1.5 text-fb-link gofio-hover-panel">
                                Verificación
                            </Link>
                        </li>
                        <li v-if="user?.is_admin">
                            <Link href="/admin" class="block rounded px-2 py-1.5 font-semibold text-amber-700 gofio-hover-panel">
                                Panel admin
                            </Link>
                        </li>
                        <li v-else-if="user?.is_staff">
                            <Link href="/admin/moderacion" class="block rounded px-2 py-1.5 font-semibold text-teal-700 gofio-hover-panel">
                                Moderación
                            </Link>
                        </li>
                    </ul>
                </div>

                <Link
                    href="/configuracion/creator-plus"
                    class="creator-plus-sidebar-cta block overflow-hidden rounded-lg border border-amber-300/60 shadow-sm transition hover:shadow-md"
                >
                    <div class="creator-plus-sidebar-cta__head px-3 py-2.5 text-white">
                        <div class="flex items-center gap-2">
                            <span class="creator-plus-sidebar-cta__icon flex h-8 w-8 items-center justify-center rounded-full text-sm">
                                <i class="fa-solid fa-crown"></i>
                            </span>
                            <div>
                                <p class="text-sm font-extrabold leading-tight">Creator Plus</p>
                                <p v-if="user?.is_creator_plus" class="text-[10px] font-semibold uppercase tracking-wide text-amber-100">
                                    Activo
                                </p>
                                <p v-else class="text-[10px] text-amber-50/90">Suscripción premium</p>
                            </div>
                        </div>
                    </div>
                    <div class="creator-plus-sidebar-cta__body px-3 py-2 text-[11px] leading-snug text-amber-950">
                        <p>Sin ads · Check verificado · Tema dorado · Karma x2</p>
                        <p class="mt-1 font-semibold text-amber-800">
                            {{ user?.is_creator_plus ? 'Ver beneficios y renovar' : 'Ver planes y activar' }}
                            <i class="fa-solid fa-arrow-right ml-0.5 text-[10px]"></i>
                        </p>
                    </div>
                </Link>
            </aside>

            <!-- Columna central: avisos flash, compositor y feed de contenido -->
            <main class="gofio-center-content min-w-0 space-y-3">
                <FlashNotice v-if="flash.success" :message="flash.success" type="success" />
                <FlashNotice v-if="flash.error" :message="flash.error" type="error" />
                <slot name="composer" />
                <slot name="feed" />
                <slot />
            </main>

            <!-- Sidebar derecha: widgets, sugerencias y espacio publicitario -->
            <aside class="gofio-right-sidebar hidden space-y-3 lg:block">
                <slot name="sidebar" />

                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header">Sugerencias</div>
                    <p class="p-3 text-sm text-fb-muted">
                        Descubre creadores destacados — próximamente.
                    </p>
                </div>

                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header">Cumpleaños</div>
                    <p class="p-3 text-sm text-fb-muted">
                        Nadie cumple años hoy.
                    </p>
                </div>

                <div v-if="features.ads_enabled" class="gofio-box overflow-hidden">
                    <div class="gofio-box-header">Publicidad</div>
                    <div class="flex h-32 items-center justify-center p-3 text-xs text-fb-muted" style="background: var(--color-panel)">
                        Espacio publicitario
                    </div>
                </div>

                <div v-else class="gofio-box gofio-sidebar-plus-banner overflow-hidden">
                    <div class="gofio-box-header">Creator Plus</div>
                    <p class="gofio-sidebar-plus-banner__text p-3 text-xs">
                        <i class="fa-solid fa-circle-check mr-1"></i>
                        Sin publicidad — gracias por apoyar a Gofio.
                    </p>
                </div>
            </aside>
        </div>

        <SiteCopyrightFooter />

        <TipNotification />
        <ChatDock ref="chatDockRef" />

        <!-- Navegación inferior móvil: categorías, estadísticas y cuenta -->
        <MobileBottomNav
            :categories="categories"
            :formatted-balance="formattedBalance"
            :is-home="isHome"
            :selected-category-slug="selectedCategorySlug"
            :profile-url="profileUrl"
            @select-category="selectCategory"
        />
    </div>
</template>
