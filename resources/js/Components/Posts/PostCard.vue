<script setup>
/**
 * Tarjeta de publicación para el feed: cabecera con autor, contenido,
 * barra de reacciones/propinas y panel colapsable de comentarios.
 */

import { Link } from '@inertiajs/vue3';
import { ref, toRef, watch } from 'vue';
import PostContentRenderer from '@/Components/Posts/PostContentRenderer.vue';
import PostReactionBar from '@/Components/Posts/PostReactionBar.vue';
import PostTipButton from '@/Components/Wallet/PostTipButton.vue';
import VerificationBadge from '@/Components/User/VerificationBadge.vue';
import RankLabel from '@/Components/User/RankLabel.vue';
import PostReportFlag from '@/Components/Moderation/PostReportFlag.vue';
import CommentThread from '@/Components/Posts/CommentThread.vue';
import { usePostViewTracker } from '@/composables/usePostViewTracker';

const props = defineProps({
    post: {
        type: Object,
        required: true,
    },
    trackViews: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['views-updated']);

const cardRef = ref(null);
const commentsOpen = ref(false);
const commentsMounted = ref(false);
const displayViewsCount = ref(props.post.views_count ?? 0);

/** Sincroniza el contador local cuando el padre refresca el post. */
watch(
    () => props.post.views_count,
    (value) => {
        if (typeof value === 'number') {
            displayViewsCount.value = value;
        }
    },
);

/** Registra visita única cuando la tarjeta es visible en el feed. */
usePostViewTracker(
    cardRef,
    toRef(() => props.post.id),
    (count) => {
        displayViewsCount.value = count;
        emit('views-updated', { id: props.post.id, views_count: count });
    },
    toRef(() => props.trackViews),
);

/** Formatea la fecha de publicación en locale español. */
const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

/** Expande o contrae el hilo de comentarios embebido. */
const toggleComments = () => {
    if (props.post.block_comments) {
        return;
    }

    commentsOpen.value = !commentsOpen.value;

    if (commentsOpen.value) {
        commentsMounted.value = true;
    }
};
</script>

<template>
    <article
        ref="cardRef"
        class="post-card gofio-box overflow-hidden"
        :class="{ 'creator-plus-post-glow': post.user?.is_creator_plus }"
    >
        <!-- Cabecera con autor, categoría y bandera de denuncia -->
        <div class="post-card-header border-b border-fb-border bg-[#F5F6F7] px-3 py-2 sm:px-4">
            <div class="post-card-header__author min-w-0">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-[#DADDE1] text-xs font-bold text-fb-muted">
                        {{ post.user.username.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-x-1">
                            <Link
                                :href="`/perfil/${post.user.username}`"
                                class="text-sm font-semibold text-fb-link hover:underline"
                            >
                                {{ post.user.username }}
                            </Link>
                            <VerificationBadge :tipo="post.user.tipo_verificacion" size="xs" />
                        </div>
                        <RankLabel :rango="post.user.rango" class="mt-0.5" />
                        <p class="truncate text-xs text-fb-muted">
                            {{ post.category?.name }} · {{ formatDate(post.created_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="post-card-header__flag">
                <PostReportFlag variant="corner" :post="post" />
            </div>
        </div>

        <!-- Título enlazado y cuerpo del post -->
        <div class="p-4">
            <Link :href="`/post/${post.slug}`" class="block hover:underline">
                <h2 class="text-lg font-bold text-fb-link">{{ post.title }}</h2>
            </Link>

            <div class="mt-3">
                <PostContentRenderer :content="post.content" />
            </div>
        </div>

        <!-- Barra de interacción: reacciones, propina, visitas y comentarios -->
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-fb-border bg-[#F5F6F7] px-4 py-2">
            <div class="flex flex-wrap items-center gap-2">
                <PostReactionBar
                    :post-id="post.id"
                    :reactions="post.reactions"
                    :points-count="post.points_count"
                />
                <PostTipButton :post="post" />
                <span class="text-xs text-fb-muted">
                    <i class="fa-regular fa-eye"></i> {{ displayViewsCount }} visitas
                </span>
            </div>

            <button
                v-if="!post.block_comments"
                type="button"
                class="flex items-center gap-1 text-sm font-semibold transition"
                :class="commentsOpen ? 'text-fb-link' : 'text-fb-muted hover:text-fb-link'"
                :aria-expanded="commentsOpen"
                @click="toggleComments"
            >
                <i class="fa-regular fa-comment"></i>
                {{ post.comments_count }} comentarios
                <i
                    class="fa-solid fa-chevron-down ml-0.5 text-[10px] transition-transform duration-200"
                    :class="{ 'rotate-180': commentsOpen }"
                ></i>
            </button>
            <span v-else class="text-xs text-fb-muted">
                <i class="fa-regular fa-comment-slash"></i> Comentarios cerrados
            </span>
        </div>

        <!-- Panel desplegable con hilo de comentarios -->
        <div
            v-if="!post.block_comments && commentsMounted"
            class="post-comments-panel"
            :class="{ 'post-comments-panel--open': commentsOpen }"
            :aria-hidden="!commentsOpen"
        >
            <div class="post-comments-panel__viewport">
                <CommentThread
                    embedded
                    lazy
                    :active="commentsOpen"
                    :post-id="post.id"
                    :post-slug="post.slug"
                    :post-owner-id="post.user.id"
                />
            </div>
        </div>
    </article>
</template>
