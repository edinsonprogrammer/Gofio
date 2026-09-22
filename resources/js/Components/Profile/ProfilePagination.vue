<script setup>
/**
 * Paginación simple para historiales del perfil (posts y Vidu).
 */

import { Link } from '@inertiajs/vue3';
import FaIcon from '@/Components/UI/FaIcon.vue';

defineProps({
    paginator: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <!-- Controles de paginación -->
    <nav
        v-if="paginator.last_page > 1"
        class="profile-pagination"
        aria-label="Paginación"
    >
        <Link
            v-if="paginator.prev_page_url"
            :href="paginator.prev_page_url"
            class="profile-pagination__btn"
            preserve-scroll
        >
            <FaIcon icon="fa-solid fa-chevron-left" />
            Anterior
        </Link>
        <span v-else class="profile-pagination__btn profile-pagination__btn--disabled">
            <FaIcon icon="fa-solid fa-chevron-left" />
            Anterior
        </span>

        <span class="profile-pagination__info">
            Página {{ paginator.current_page }} de {{ paginator.last_page }}
        </span>

        <Link
            v-if="paginator.next_page_url"
            :href="paginator.next_page_url"
            class="profile-pagination__btn"
            preserve-scroll
        >
            Siguiente
            <FaIcon icon="fa-solid fa-chevron-right" />
        </Link>
        <span v-else class="profile-pagination__btn profile-pagination__btn--disabled">
            Siguiente
            <FaIcon icon="fa-solid fa-chevron-right" />
        </span>
    </nav>
</template>
