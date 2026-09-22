<script setup>
/**
 * Historial completo de publicaciones de un usuario con paginación (15 por página).
 */

import { Link } from '@inertiajs/vue3';
import GofioLayout from '@/Layouts/GofioLayout.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import VerificationBadge from '@/Components/User/VerificationBadge.vue';
import ProfilePagination from '@/Components/Profile/ProfilePagination.vue';

defineProps({
    profile: {
        type: Object,
        required: true,
    },
    posts: {
        type: Object,
        required: true,
    },
    isOwnProfile: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <GofioLayout>
        <template #feed>
            <!-- Cabecera con enlace de regreso al perfil -->
            <div class="gofio-box overflow-hidden">
                <div class="gofio-box-header flex items-center gap-2">
                    <Link
                        :href="`/perfil/${profile.username}`"
                        class="text-white hover:opacity-80"
                        title="Volver al perfil"
                    >
                        <FaIcon icon="fa-solid fa-chevron-left" />
                    </Link>
                    <FaIcon icon="fa-solid fa-newspaper" />
                    Publicaciones de {{ profile.username }}
                </div>

                <div class="flex items-center gap-3 border-b border-fb-border bg-[#F5F6F7] px-4 py-3">
                    <div class="profile-content-avatar">
                        <img v-if="profile.avatar_url" :src="profile.avatar_url" alt="" class="h-full w-full object-cover" />
                        <span v-else>{{ profile.username.charAt(0).toUpperCase() }}</span>
                    </div>
                    <div>
                        <p class="flex items-center gap-1 font-semibold text-textPrincipal">
                            {{ profile.username }}
                            <VerificationBadge :tipo="profile.tipo_verificacion" size="sm" />
                        </p>
                        <p v-if="profile.nick" class="text-xs text-fb-muted">@{{ profile.nick }}</p>
                    </div>
                </div>
            </div>

            <!-- Listado paginado -->
            <div class="gofio-box mt-4 overflow-hidden">
                <div class="profile-content-section-header">
                    <FaIcon icon="fa-solid fa-list" />
                    <span>{{ posts.total }} publicación{{ posts.total === 1 ? '' : 'es' }}</span>
                </div>

                <ul v-if="posts.data.length" class="profile-posts-list">
                    <li v-for="post in posts.data" :key="post.id" class="profile-posts-list__item">
                        <Link :href="`/post/${post.slug}`" class="profile-posts-list__link">
                            <div class="profile-posts-list__main">
                                <FaIcon icon="fa-solid fa-file-lines" class="profile-posts-list__icon" />
                                <div class="min-w-0 flex-1">
                                    <p class="profile-posts-list__title">{{ post.title }}</p>
                                    <p class="profile-posts-list__meta">
                                        <span v-if="post.category">{{ post.category.name }}</span>
                                        <span v-if="post.category"> · </span>
                                        {{ post.created_at_human }}
                                    </p>
                                </div>
                            </div>
                            <div class="profile-posts-list__stats">
                                <span title="Puntos"><FaIcon icon="fa-solid fa-arrow-up" /> {{ post.points_count }}</span>
                                <span title="Comentarios"><FaIcon icon="fa-solid fa-comment" /> {{ post.comments_count }}</span>
                                <span title="Vistas"><FaIcon icon="fa-solid fa-eye" /> {{ post.views_count }}</span>
                            </div>
                        </Link>
                    </li>
                </ul>

                <div v-else class="p-8 text-center text-sm text-fb-muted">
                    <FaIcon icon="fa-solid fa-inbox" class="mb-2 text-2xl opacity-50" />
                    <p>{{ isOwnProfile ? 'Aún no has publicado posts.' : 'Este usuario no tiene publicaciones visibles.' }}</p>
                </div>

                <div v-if="posts.data.length" class="border-t border-fb-border p-4">
                    <ProfilePagination :paginator="posts" />
                </div>
            </div>
        </template>
    </GofioLayout>
</template>
