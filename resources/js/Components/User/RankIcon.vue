<script setup>

/**

 * Icono compacto del rango de un usuario para espacios reducidos (comentarios, listas).

 * Muestra solo el símbolo con color del rango y tooltip con el nombre.

 */



import { computed } from 'vue';

import { usePage } from '@inertiajs/vue3';

import FaIcon from '@/Components/UI/FaIcon.vue';



const props = defineProps({

    rango: {

        type: Object,

        default: null,

    },

    size: {

        type: String,

        default: 'xs',

    },

});



const page = usePage();



/** Icono final: override del paquete activo o icono definido en el rango. */

const icon = computed(() => {

    const override = props.rango?.slug && page.props.iconPack?.ranks?.[props.rango.slug];

    return override || props.rango?.icon;

});



/** Clases de tamaño del icono según la variante solicitada. */

const sizeClass = computed(() => (props.size === 'sm' ? 'text-[0.82rem]' : 'text-[0.72rem]'));

</script>



<template>

    <!-- Icono del rango junto al nombre del comentarista -->

    <span

        v-if="rango"

        class="inline-flex shrink-0 items-center justify-center leading-none"

        :class="sizeClass"

        :style="{ color: rango.color }"

        :title="`Rango: ${rango.nombre}`"

        :aria-label="`Rango: ${rango.nombre}`"

    >

        <FaIcon :icon="icon" />

    </span>

</template>


