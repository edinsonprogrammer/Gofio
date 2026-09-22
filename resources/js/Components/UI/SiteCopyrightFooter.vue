<script setup>

/**

 * Pie de página con la leyenda de copyright centralizada de Gofio.

 */



import { computed } from 'vue';

import { usePage } from '@inertiajs/vue3';

import { SITE_COPYRIGHT } from '@/constants/siteCopyright';



const page = usePage();



/** Texto legal editable desde configuración del sitio o fallback estático. */

const copyrightText = computed(() => page.props.site?.site_copyright ?? SITE_COPYRIGHT);

</script>



<template>

    <!-- Barra inferior con texto legal y crédito del desarrollador -->

    <footer class="border-t border-fb-border py-4 text-center text-xs text-fb-muted">

        {{ copyrightText }}

    </footer>

</template>


