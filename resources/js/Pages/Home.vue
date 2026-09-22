<script setup>
/**
 * Página de inicio autenticada: carrusel de funciones, bienvenida temporal,
 * compositor de publicaciones y feed infinito filtrable por categoría.
 */

import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import GofioLayout from '@/Layouts/GofioLayout.vue';
import PostComposer from '@/Components/Posts/PostComposer.vue';
import FeedList from '@/Components/Posts/FeedList.vue';
import HomeFeaturesCarousel from '@/Components/Home/HomeFeaturesCarousel.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';

defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const feedRef = ref(null);
const showWelcome = ref(page.props.sessionWelcome?.active ?? false);
let hideTimer = null;

/** Slug de categoría activa desde el query string de la URL. */
const selectedCategorySlug = computed(() => {
    const queryPart = page.url.includes('?') ? page.url.split('?')[1] : '';

    return new URLSearchParams(queryPart).get('categoria') || null;
});

/** Programa el ocultamiento automático del banner de bienvenida. */
const scheduleHide = (remainingMs) => {
    if (hideTimer) {
        clearTimeout(hideTimer);
        hideTimer = null;
    }

    if (!remainingMs || remainingMs <= 0) {
        showWelcome.value = false;
        return;
    }

    hideTimer = setTimeout(() => {
        showWelcome.value = false;
        hideTimer = null;
    }, remainingMs);
};

/** Sincroniza visibilidad del saludo con el estado de sesión del servidor. */
const syncWelcome = (welcome) => {
    if (!welcome?.active) {
        showWelcome.value = false;
        if (hideTimer) {
            clearTimeout(hideTimer);
            hideTimer = null;
        }
        return;
    }

    showWelcome.value = true;
    scheduleHide(welcome.remaining_ms ?? 0);
};

/** Inserta la nueva publicación al inicio del feed sin recargar la página. */
const onPostCreated = (post) => {
    feedRef.value?.prependPost(post);
};

onMounted(() => {
    syncWelcome(page.props.sessionWelcome);
});

watch(
    () => page.props.sessionWelcome,
    (welcome) => syncWelcome(welcome),
    { deep: true },
);

onBeforeUnmount(() => {
    if (hideTimer) {
        clearTimeout(hideTimer);
    }
});
</script>

<template>
    <GofioLayout :categories="categories">
        <template #composer>
            <HomeFeaturesCarousel />

            <!-- Banner de bienvenida que desaparece tras unos segundos -->
            <Transition name="home-welcome-fade">
                <div v-if="showWelcome" class="home-welcome mb-3 px-4 py-3">
                    <p class="home-welcome-title">
                        <FaIcon icon="fa-solid fa-pen-nib" class="home-welcome-icon" />
                        Bienvenido, {{ page.props.auth.user?.username }}
                    </p>
                    <p class="mt-0.5 text-xs text-fb-muted sm:text-sm">
                        Comparte publicaciones e interactúa con la comunidad desde tu panel principal.
                    </p>
                </div>
            </Transition>

            <PostComposer :categories="categories" @created="onPostCreated" />
        </template>

        <template #feed>
            <FeedList ref="feedRef" :category-slug="selectedCategorySlug" />
        </template>
    </GofioLayout>
</template>

<style scoped>
.home-welcome-fade-leave-active {
    transition: opacity 0.35s ease, transform 0.35s ease, max-height 0.35s ease, margin 0.35s ease;
    overflow: hidden;
}

.home-welcome-fade-leave-to {
    opacity: 0;
    transform: translateY(-6px);
    max-height: 0;
    margin-bottom: 0 !important;
}
</style>
