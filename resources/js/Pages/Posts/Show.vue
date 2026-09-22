<script setup>
/**
 * Vista detallada de una publicación con contenido completo, reacciones y hilo de comentarios.
 */

import { Link } from '@inertiajs/vue3';
import GofioLayout from '@/Layouts/GofioLayout.vue';
import PostContentRenderer from '@/Components/Posts/PostContentRenderer.vue';
import SeoHead from '@/Components/SEO/SeoHead.vue';
import PostReactionBar from '@/Components/Posts/PostReactionBar.vue';
import CommentThread from '@/Components/Posts/CommentThread.vue';
import PostTipButton from '@/Components/Wallet/PostTipButton.vue';
import VerificationBadge from '@/Components/User/VerificationBadge.vue';
import RankLabel from '@/Components/User/RankLabel.vue';
import PostReportFlag from '@/Components/Moderation/PostReportFlag.vue';

defineProps({
    post: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    seo: {
        type: Object,
        default: null,
    },
});


/** Formatea una fecha ISO en formato local español. */
const formatDate = (iso) => new Date(iso).toLocaleDateString('es-ES', {
    day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
});
</script>

<template>
    <!-- Vista completa de publicación con comentarios -->

    <SeoHead :seo="seo" />
    <GofioLayout :categories="categories">
        <template #feed>
            <article
                class="post-card gofio-box overflow-hidden"
                :class="{ 'creator-plus-post-glow': post.user?.is_creator_plus }"
            >
                <div class="post-card-header border-b border-fb-border bg-[#F5F6F7] px-3 py-3 sm:px-4">
                    <div class="post-card-header__author min-w-0">
                        <div class="flex items-center gap-2">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-[#DADDE1] text-sm font-bold">
                                {{ post.user.username.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-x-1">
                                    <Link :href="`/perfil/${post.user.username}`" class="font-semibold text-fb-link hover:underline">
                                        {{ post.user.username }}
                                    </Link>
                                    <VerificationBadge :tipo="post.user.tipo_verificacion" size="xs" />
                                </div>
                                <RankLabel :rango="post.user.rango" class="mt-0.5" />
                                <p class="text-xs text-fb-muted">
                                    {{ post.category?.name }} · {{ formatDate(post.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="post-card-header__flag">
                        <PostReportFlag variant="corner" :post="post" />
                    </div>
                </div>

                <div class="p-4">
                    <h1 class="text-2xl font-bold">{{ post.title }}</h1>
                    <p v-if="post.tags" class="mt-1 text-xs text-fb-muted">Etiquetas: {{ post.tags }}</p>
                    <div class="mt-4">
                        <PostContentRenderer :content="post.content" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-fb-border bg-[#F5F6F7] px-4 py-2">
                    <PostReactionBar
                        :post-id="post.id"
                        :reactions="post.reactions"
                        :points-count="post.points_count"
                    />
                    <PostTipButton :post="post" />
                    <span class="text-sm text-fb-muted">
                        <i class="fa-regular fa-eye"></i> {{ post.views_count }} visitas
                    </span>
                    <span class="text-sm text-fb-muted">
                        <i class="fa-regular fa-comment"></i> {{ post.comments_count }} comentarios
                    </span>
                </div>
            </article>

            <div v-if="!post.block_comments" class="mt-4">
                <CommentThread
                    :post-id="post.id"
                    :post-slug="post.slug"
                    :post-owner-id="post.user.id"
                />
            </div>
            <div v-else class="gofio-box mt-4 p-4 text-center text-sm text-fb-muted">
                Los comentarios están deshabilitados en este post.
            </div>

            <div class="mt-4">
                <Link href="/" class="text-sm font-semibold text-fb-link hover:underline">
                    ← Volver al inicio
                </Link>
            </div>
        </template>
    </GofioLayout>
</template>
