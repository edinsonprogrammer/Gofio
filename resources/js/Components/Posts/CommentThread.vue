<script setup>

/**

 * Hilo de comentarios anidados con respuestas, reacciones y paginación.

 */



import { Link, usePage } from '@inertiajs/vue3';

import { computed, nextTick, onMounted, ref, watch } from 'vue';

import CommentComposer from '@/Components/Posts/CommentComposer.vue';

import CommentSkeleton from '@/Components/Posts/CommentSkeleton.vue';

import CommentAuthorMeta from '@/Components/Posts/CommentAuthorMeta.vue';

import ContentActionsMenu from '@/Components/Moderation/ContentActionsMenu.vue';



const props = defineProps({

    postId: {

        type: Number,

        required: true,

    },

    postSlug: {

        type: String,

        required: true,

    },

    postOwnerId: {

        type: Number,

        default: null,

    },

    embedded: {

        type: Boolean,

        default: false,

    },

    lazy: {

        type: Boolean,

        default: false,

    },

    active: {

        type: Boolean,

        default: true,

    },

});



const comments = ref([]);

const loading = ref(false);

const hasFetched = ref(false);

const submitting = ref(false);

const newComment = ref('');

const newCommentImage = ref('');

const replyTo = ref(null);

const replyContent = ref('');

const replyImage = ref('');

const error = ref('');

const page = usePage();
const commentComposer = computed(() => page.props.commentComposer ?? null);

/** Contador local de comentarios hoy; se sincroniza tras publicar sin recargar la página. */
const commentsTodayLocal = ref(null);

const commentsToday = computed(() => (
    commentsTodayLocal.value ?? commentComposer.value?.comments_today ?? 0
));

const maxCommentsPerDay = computed(() => commentComposer.value?.max_comments_per_day ?? null);

const canComment = computed(() => {
    if (commentComposer.value?.can_create === false) {
        return false;
    }

    if (maxCommentsPerDay.value === null) {
        return true;
    }

    return commentsToday.value < maxCommentsPerDay.value;
});


/** Consulta los comentarios del post vía API. */

const fetchComments = async () => {

    if (loading.value) {

        return;

    }



    loading.value = true;

    error.value = '';



    try {

        const { data } = await window.axios.get(`/api/posts/${props.postSlug}/comments`);

        comments.value = data.data;

        hasFetched.value = true;

        await nextTick();

        scrollToHashComment();

    } catch {

        error.value = 'No se pudieron cargar los comentarios.';

    } finally {

        loading.value = false;

    }

};



/** Carga comentarios solo si el hilo está activo o visible. */

const maybeFetchComments = () => {

    if (props.lazy) {

        if (props.active && !hasFetched.value) {

            fetchComments();

        }

        return;

    }



    if (!hasFetched.value) {

        fetchComments();

    }

};



watch(() => props.active, () => {

    maybeFetchComments();

});



onMounted(() => {

    maybeFetchComments();

});



/** Desplaza la vista al comentario indicado en el hash de URL. */

const scrollToHashComment = () => {

    const hash = window.location.hash;

    if (!hash.startsWith('#comment-')) {

        return;

    }



    const target = document.querySelector(hash);

    if (target) {

        target.scrollIntoView({ behavior: 'smooth', block: 'center' });

        target.classList.add('comment-highlight');

        setTimeout(() => target.classList.remove('comment-highlight'), 2400);

    }

};



/** Muestra u oculta el compositor de respuesta anidada. */

const toggleReply = (commentId) => {

    if (replyTo.value === commentId) {

        replyTo.value = null;

        replyContent.value = '';

        replyImage.value = '';

        return;

    }



    replyTo.value = commentId;

    replyContent.value = '';

    replyImage.value = '';

};



/** Publica el comentario o respuesta mediante la API. */

const submitComment = async (parentId = null) => {

    const content = parentId ? replyContent.value : newComment.value;

    const imageUrl = parentId ? replyImage.value : newCommentImage.value;



    if (!content.trim() && !imageUrl) {

        return;

    }

    if (!canComment.value) {
        error.value = 'Has alcanzado tu límite diario de comentarios.';
        return;
    }


    submitting.value = true;

    error.value = '';



    try {

        const payload = {

            content,

            parent_id: parentId,

        };



        if (imageUrl) {

            payload.image_url = imageUrl;

        }



        const { data } = await window.axios.post(`/api/posts/${props.postSlug}/comments`, payload);



        if (parentId) {

            const parent = comments.value.find((c) => c.id === parentId);

            if (parent) {

                parent.replies = [...(parent.replies || []), data.data];

            }

            replyTo.value = null;

            replyContent.value = '';

            replyImage.value = '';

        } else {

            comments.value = [...comments.value, data.data];

            newComment.value = '';

            newCommentImage.value = '';

        }

        commentsTodayLocal.value = commentsToday.value + 1;

    } catch (e) {

        error.value = e.response?.data?.message

            || e.response?.data?.errors?.content?.[0]

            || 'Error al comentar.';

    } finally {

        submitting.value = false;

    }

};



/** Registra voto positivo o negativo en un comentario. */

const voteComment = async (comment) => {

    try {

        const { data } = await window.axios.post(`/api/comments/${comment.id}/vote`);

        if (!data.frozen) {

            comment.points_count += data.points_given;

        }

    } catch (e) {

        alert(e.response?.data?.errors?.vote?.[0] || 'No se pudo votar.');

    }

};



/** Formatea una fecha ISO en formato local español. */

const formatDate = (iso) => new Date(iso).toLocaleDateString('es-ES', {

    day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',

});



/** Genera la URL absoluta para compartir un comentario. */

const commentShareUrl = (commentId) => `${window.location.origin}/post/${props.postSlug}#comment-${commentId}`;

</script>



<template>

    <!-- Listado de comentarios con respuestas anidadas -->

    <div :class="embedded ? 'comment-thread-embedded bg-white' : 'gofio-box overflow-hidden'">

        <div v-if="!embedded" class="gofio-box-header flex flex-wrap items-center justify-between gap-2">
            <span>Comentarios</span>
            <span v-if="commentComposer" class="text-xs font-normal opacity-80">
                {{ commentsToday }}/{{ maxCommentsPerDay }} hoy
            </span>
        </div>



        <div :class="embedded ? 'border-t border-fb-border p-3' : 'border-b border-fb-border p-4'">

            <CommentComposer

                v-model="newComment"

                v-model:image-url="newCommentImage"

                :compact="embedded"

                :disabled="submitting || loading"

                :comments-today="commentsToday"

                :max-comments-per-day="maxCommentsPerDay"

                :can-create="canComment"

                submit-label="Comentar"

                @submit="submitComment()"

            />

        </div>



        <p v-if="error" class="px-4 py-2 text-xs text-red-600">{{ error }}</p>



        <CommentSkeleton v-if="loading" :count="embedded ? 2 : 3" :compact="embedded" />



        <ul v-else class="divide-y divide-fb-border">

            <li

                v-for="comment in comments"

                :key="comment.id"

                :id="`comment-${comment.id}`"

                :class="embedded ? 'px-3 py-3' : 'p-4'"

            >

                <div class="flex gap-2">

                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-[#DADDE1] text-xs font-bold">

                        {{ comment.user.username.charAt(0).toUpperCase() }}

                    </div>



                    <div class="min-w-0 flex-1">

                        <div class="flex items-start justify-between gap-2">

                            <CommentAuthorMeta

                                :user="comment.user"

                                :created-at="comment.created_at"

                                :post-owner-id="postOwnerId"

                                :format-date="formatDate"

                            />



                            <ContentActionsMenu

                                type="comment"

                                :target-id="comment.id"

                                :owner-id="comment.user.id"

                                :share-url="commentShareUrl(comment.id)"

                                :share-title="`Comentario de @${comment.user.username}`"

                            />

                        </div>



                        <p v-if="comment.content" class="mt-1 whitespace-pre-wrap text-sm">{{ comment.content }}</p>



                        <img

                            v-if="comment.image_url"

                            :src="comment.image_url"

                            alt="Imagen del comentario"

                            class="comment-thread-image mt-2"

                        />



                        <div class="mt-2 flex gap-3 text-xs">

                            <button

                                type="button"

                                class="font-semibold text-brandColor hover:underline"

                                @click="voteComment(comment)"

                            >

                                <i class="fa-regular fa-thumbs-up"></i> {{ comment.points_count }}

                            </button>

                            <button

                                type="button"

                                class="font-semibold text-fb-muted hover:underline"

                                @click="toggleReply(comment.id)"

                            >

                                Responder

                            </button>

                        </div>



                        <div v-if="replyTo === comment.id" class="mt-3">

                            <CommentComposer

                                v-model="replyContent"

                                v-model:image-url="replyImage"

                                compact

                                :disabled="submitting"

                                :comments-today="commentsToday"

                                :max-comments-per-day="maxCommentsPerDay"

                                :can-create="canComment"

                                placeholder="Tu respuesta..."

                                submit-label="Responder"

                                @submit="submitComment(comment.id)"

                            />

                        </div>



                        <ul v-if="comment.replies?.length" class="mt-3 space-y-3 border-l-2 border-fb-border pl-4">

                            <li v-for="reply in comment.replies" :key="reply.id" :id="`comment-${reply.id}`">

                                <div class="flex items-start justify-between gap-2">

                                    <CommentAuthorMeta

                                        :user="reply.user"

                                        :created-at="reply.created_at"

                                        :post-owner-id="postOwnerId"

                                        :format-date="formatDate"

                                    />



                                    <ContentActionsMenu

                                        type="comment"

                                        :target-id="reply.id"

                                        :owner-id="reply.user.id"

                                        :share-url="commentShareUrl(reply.id)"

                                        :share-title="`Comentario de @${reply.user.username}`"

                                    />

                                </div>



                                <p v-if="reply.content" class="mt-1 whitespace-pre-wrap text-sm">{{ reply.content }}</p>



                                <img

                                    v-if="reply.image_url"

                                    :src="reply.image_url"

                                    alt="Imagen de la respuesta"

                                    class="comment-thread-image mt-2"

                                />



                                <button

                                    type="button"

                                    class="mt-1 text-xs font-semibold text-brandColor hover:underline"

                                    @click="voteComment(reply)"

                                >

                                    <i class="fa-regular fa-thumbs-up"></i> {{ reply.points_count }}

                                </button>

                            </li>

                        </ul>

                    </div>

                </div>

            </li>

        </ul>



        <p v-if="!loading && hasFetched && comments.length === 0" class="p-4 text-center text-sm text-fb-muted">

            Sé el primero en comentar.

        </p>



        <div v-if="embedded" class="border-t border-fb-border bg-[#F5F6F7] px-3 py-2 text-center">

            <Link

                :href="`/post/${postSlug}`"

                class="text-xs font-semibold text-fb-link hover:underline"

            >

                Ver publicación completa y todos los comentarios →

            </Link>

        </div>

    </div>

</template>


