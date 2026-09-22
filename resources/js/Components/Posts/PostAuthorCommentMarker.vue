<script setup>

/**

 * Indicador dorado de que el comentarista es el autor de la publicación.

 * No se muestra para miembros del staff (moderación/administración).

 */



import FaIcon from '@/Components/UI/FaIcon.vue';

</script>



<template>

    <!-- Lápiz dorado: autor del post comentando en su publicación -->

    <span

        class="post-author-comment-marker inline-flex items-center justify-center text-[0.68rem] leading-none"

        title="Autor de la publicación"

        aria-label="Autor de la publicación"

    >

        <FaIcon icon="fa-solid fa-pencil" />

    </span>

</template>


