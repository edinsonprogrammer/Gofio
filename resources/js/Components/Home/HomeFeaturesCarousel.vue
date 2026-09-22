<script setup>
/**
 * Carrusel horizontal de módulos de Gofio: Vidu activo y otros en desarrollo.
 */

import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import FaIcon from '@/Components/UI/FaIcon.vue';

/** Módulos destacados del feed principal. */
const items = [
    {
        id: 'vidu',
        label: 'Vidu',
        icon: 'fa-solid fa-clapperboard',
        href: '/vidu',
    },
    {
        id: 'canales',
        label: 'Canales',
        icon: 'fa-solid fa-tv',
    },
    {
        id: 'mix',
        label: 'Mix',
        icon: 'fa-solid fa-music',
    },
];

const notice = ref('');
let noticeTimer = null;

/** Muestra un mensaje temporal al pulsar un módulo no implementado. */
const showComingSoon = (label) => {
    notice.value = `${label} estará disponible próximamente.`;

    if (noticeTimer) {
        clearTimeout(noticeTimer);
    }

    noticeTimer = setTimeout(() => {
        notice.value = '';
        noticeTimer = null;
    }, 3200);
};
</script>

<template>
    <!-- Carrusel de accesos rápidos a módulos de Gofio -->
    <section class="home-features-carousel gofio-box overflow-hidden" aria-label="Módulos de Gofio">
        <div class="home-features-carousel__track">
            <template v-for="item in items" :key="item.id">
                <Link
                    v-if="item.href"
                    :href="item.href"
                    class="home-features-carousel__item"
                    :class="`home-features-carousel__item--${item.id}`"
                >
                    <span class="home-features-carousel__icon-wrap">
                        <FaIcon :icon="item.icon" class="home-features-carousel__icon" />
                    </span>
                    <span class="home-features-carousel__label">{{ item.label }}</span>
                </Link>
                <button
                    v-else
                    type="button"
                    class="home-features-carousel__item"
                    :class="`home-features-carousel__item--${item.id}`"
                    @click="showComingSoon(item.label)"
                >
                    <span class="home-features-carousel__icon-wrap">
                        <FaIcon :icon="item.icon" class="home-features-carousel__icon" />
                    </span>
                    <span class="home-features-carousel__label">{{ item.label }}</span>
                    <span class="home-features-carousel__soon">Próximamente</span>
                </button>
            </template>
        </div>

        <p v-if="notice" class="home-features-carousel__notice" role="status">
            {{ notice }}
        </p>
    </section>
</template>
