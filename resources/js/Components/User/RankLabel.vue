<script setup>

/**

 * Etiqueta compacta del nombre de rango, sin icono, con color del rango.

 * Se usa bajo el nombre de usuario en posts y comentarios.

 */



defineProps({

    rango: {

        type: Object,

        default: null,

    },

});

</script>



<template>

    <!-- Nombre del rango en tipografía pequeña y color del nivel -->

    <p

        v-if="rango?.nombre"

        class="rank-label"

        :style="{ color: rango.color }"

        :title="`Rango: ${rango.nombre}`"

    >

        {{ rango.nombre }}

    </p>

</template>


