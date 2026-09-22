<script setup>

/**

 * Paginación moderna para listados del panel de administración.

 */



import { computed } from 'vue';

import { Link } from '@inertiajs/vue3';

import FaIcon from '@/Components/UI/FaIcon.vue';



const props = defineProps({

    paginator: {

        type: Object,

        required: true,

    },

    queryKey: {

        type: String,

        default: 'page',

    },

    itemLabel: {

        type: String,

        default: 'registros',

    },

});



/** Páginas visibles alrededor de la actual (máximo 5). */

const visiblePages = computed(() => {

    const current = props.paginator.current_page ?? 1;

    const last = props.paginator.last_page ?? 1;

    const max = 5;



    if (last <= max) {

        return Array.from({ length: last }, (_, index) => index + 1);

    }



    let start = Math.max(1, current - Math.floor(max / 2));

    let end = start + max - 1;



    if (end > last) {

        end = last;

        start = end - max + 1;

    }



    return Array.from({ length: end - start + 1 }, (_, index) => start + index);

});



/** Construye la URL de una página conservando la ruta base del paginador. */

const pageUrl = (page) => {

    const base = props.paginator.path ?? window.location.pathname;

    const separator = base.includes('?') ? '&' : '?';

    return `${base}${separator}${props.queryKey}=${page}`;

};

</script>



<template>

    <!-- Controles de paginación del historial admin -->

    <nav

        v-if="paginator.last_page > 1"

        class="admin-pagination"

        :aria-label="`Paginación de ${itemLabel}`"

    >

        <p v-if="paginator.total" class="admin-pagination__summary">

            Mostrando {{ paginator.from }}–{{ paginator.to }} de {{ paginator.total }} {{ itemLabel }}

        </p>



        <div class="admin-pagination__controls">

            <Link

                v-if="paginator.prev_page_url"

                :href="paginator.prev_page_url"

                class="admin-pagination__btn"

                preserve-scroll

            >

                <FaIcon icon="fa-solid fa-chevron-left" />

                Anterior

            </Link>

            <span v-else class="admin-pagination__btn admin-pagination__btn--disabled">

                <FaIcon icon="fa-solid fa-chevron-left" />

                Anterior

            </span>



            <div class="admin-pagination__pages">

                <Link

                    v-for="page in visiblePages"

                    :key="page"

                    :href="pageUrl(page)"

                    class="admin-pagination__page"

                    :class="{ 'admin-pagination__page--active': page === paginator.current_page }"

                    preserve-scroll

                >

                    {{ page }}

                </Link>

            </div>



            <Link

                v-if="paginator.next_page_url"

                :href="paginator.next_page_url"

                class="admin-pagination__btn"

                preserve-scroll

            >

                Siguiente

                <FaIcon icon="fa-solid fa-chevron-right" />

            </Link>

            <span v-else class="admin-pagination__btn admin-pagination__btn--disabled">

                Siguiente

                <FaIcon icon="fa-solid fa-chevron-right" />

            </span>

        </div>

    </nav>

</template>


