<script setup>
/**
 * Lista infinita del feed principal con paginación y filtro por categoría.
 */

import { onMounted, onUnmounted, ref, watch } from 'vue';

import PostCard from '@/Components/Posts/PostCard.vue';

import PostSkeleton from '@/Components/Posts/PostSkeleton.vue';



const props = defineProps({

    categorySlug: {

        type: String,

        default: null,

    },

});



const posts = ref([]);

const loading = ref(true);

const loadingMore = ref(false);

const page = ref(1);

const lastPage = ref(1);

const error = ref('');

const sentinel = ref(null);

let observer = null;




/** Consulta  posts. */
const fetchPosts = async (pageNum = 1, append = false) => {

    if (pageNum === 1) {

        loading.value = true;

    } else {

        loadingMore.value = true;

    }



    error.value = '';



    try {

        const params = { page: pageNum, per_page: 10 };



        if (props.categorySlug) {

            params.categoria = props.categorySlug;

        }



        const { data } = await window.axios.get('/api/posts', { params });



        if (append) {

            posts.value = [...posts.value, ...data.data];

        } else {

            posts.value = data.data;

        }



        page.value = data.meta.current_page;

        lastPage.value = data.meta.last_page;

    } catch (e) {

        if (e.response?.status === 404 && props.categorySlug) {

            error.value = 'Categoría no encontrada.';

        } else {

            error.value = 'No se pudo cargar el feed.';

        }

    } finally {

        loading.value = false;

        loadingMore.value = false;

    }

};




/** Solicita la siguiente página de iconos para scroll infinito. */
const loadMore = () => {

    if (loadingMore.value || page.value >= lastPage.value) return;

    fetchPosts(page.value + 1, true);

};




/** Inserta una publicación nueva al inicio del feed local. */
const prependPost = (post) => {

    if (props.categorySlug && post.category?.slug !== props.categorySlug) {

        return;

    }



    posts.value = [post, ...posts.value];

};




/** Configura el IntersectionObserver para scroll infinito. */
const setupObserver = () => {

    observer?.disconnect();

    observer = null;



    if (!sentinel.value) return;



    observer = new IntersectionObserver(

        (entries) => {

            if (entries[0].isIntersecting) {

                loadMore();

            }

        },

        { rootMargin: '200px' },

    );



    observer.observe(sentinel.value);

};




/** Vacía el feed y vuelve a cargar desde la primera página. */
const resetAndFetch = async () => {

    page.value = 1;

    lastPage.value = 1;

    posts.value = [];

    await fetchPosts(1, false);

    setupObserver();

};



watch(

    () => props.categorySlug,

    () => {

        resetAndFetch();

    },

);



onMounted(async () => {

    await fetchPosts();

    setupObserver();

});



onUnmounted(() => {

    observer?.disconnect();

});



/** Actualiza el contador de visitas de un post en la lista local. */
const onViewsUpdated = ({ id, views_count }) => {
    const post = posts.value.find((item) => item.id === id);
    if (post) {
        post.views_count = views_count;
    }
};

defineExpose({ prependPost, refresh: () => resetAndFetch() });
</script>



<template>
    <!-- Feed de publicaciones con carga infinita -->


    <div class="space-y-3">

        <template v-if="loading">

            <PostSkeleton v-for="n in 3" :key="n" />

        </template>



        <p v-else-if="error" class="gofio-box p-4 text-center text-sm text-red-600">

            {{ error }}

        </p>



        <template v-else-if="posts.length === 0">

            <div class="gofio-box p-8 text-center">

                <i class="fa-regular fa-newspaper mb-3 text-4xl text-fb-muted"></i>

                <h2 class="text-lg font-semibold">

                    {{ categorySlug ? 'Sin posts en esta categoría' : 'Sin posts aún' }}

                </h2>

                <p class="mt-2 text-sm text-fb-muted">

                    {{ categorySlug

                        ? 'Prueba otra categoría o vuelve a ver todas las publicaciones.'

                        : 'Sé el primero en publicar algo.' }}

                </p>

            </div>

        </template>



        <template v-else>

            <PostCard
                v-for="post in posts"
                :key="post.id"
                :post="post"
                track-views
                @views-updated="onViewsUpdated"
            />

        </template>



        <div v-if="loadingMore" class="space-y-3">

            <PostSkeleton />

        </div>



        <div ref="sentinel" class="h-1" />



        <p v-if="!loading && page >= lastPage && posts.length > 0" class="py-4 text-center text-xs text-fb-muted">

            Has llegado al final del feed.

        </p>

    </div>

</template>


