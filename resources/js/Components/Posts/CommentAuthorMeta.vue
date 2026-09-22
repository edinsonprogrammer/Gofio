<script setup>

/**

 * Cabecera de autor en comentarios: nombre, rango, fecha y marcador de autor del post.

 */



import RankLabel from '@/Components/User/RankLabel.vue';

import PostAuthorCommentMarker from '@/Components/Posts/PostAuthorCommentMarker.vue';



const props = defineProps({

    user: {

        type: Object,

        required: true,

    },

    createdAt: {

        type: String,

        required: true,

    },

    postOwnerId: {

        type: Number,

        default: null,

    },

    formatDate: {

        type: Function,

        required: true,

    },

});



/** Indica si debe mostrarse el lápiz dorado de autor del post (no aplica a staff). */

const isPostAuthorComment = () => (

    props.postOwnerId != null

    && props.user?.id === props.postOwnerId

    && !props.user?.is_staff

);

</script>



<template>

    <!-- Identidad del comentarista: nombre, rango y fecha -->

    <div>

        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 text-sm leading-snug">

            <span class="font-semibold text-fb-link">{{ user.username }}</span>

            <span class="text-xs text-fb-muted">{{ formatDate(createdAt) }}</span>

        </div>

        <RankLabel :rango="user.rango" class="mt-0.5" />

        <PostAuthorCommentMarker v-if="isPostAuthorComment()" class="mt-0.5" />

    </div>

</template>


