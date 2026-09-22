<script setup>
/**
 * Historial completo de videos Vidu de un usuario con paginación (15 por página).
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
    videos: {
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
                    <FaIcon icon="fa-solid fa-film" />
                    Vidu de {{ profile.username }}
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

            <!-- Grid paginado de videos -->
            <div class="gofio-box mt-4 overflow-hidden">
                <div class="profile-content-section-header profile-content-section-header--vidu">
                    <FaIcon icon="fa-solid fa-clapperboard" />
                    <span>{{ videos.total }} video{{ videos.total === 1 ? '' : 's' }} Vidu</span>
                </div>

                <div v-if="videos.data.length" class="profile-vidu-grid">
                    <Link
                        v-for="video in videos.data"
                        :key="video.id"
                        :href="`/vidu/${video.id}`"
                        class="profile-vidu-card"
                    >
                        <div class="profile-vidu-card__thumb">
                            <img
                                v-if="video.thumbnail_url"
                                :src="video.thumbnail_url"
                                :alt="video.title"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                            <div v-else class="profile-vidu-card__placeholder">
                                <FaIcon icon="fa-solid fa-play" />
                            </div>
                            <span class="profile-vidu-card__duration">{{ video.duration_label }}</span>
                        </div>
                        <div class="profile-vidu-card__body">
                            <p class="profile-vidu-card__title">{{ video.title }}</p>
                            <p class="profile-vidu-card__meta">
                                <span><FaIcon icon="fa-solid fa-heart" /> {{ video.likes_count }}</span>
                                <span><FaIcon icon="fa-solid fa-eye" /> {{ video.views_count }}</span>
                                <span>{{ video.created_at_human }}</span>
                            </p>
                        </div>
                    </Link>
                </div>

                <div v-else class="p-8 text-center text-sm text-fb-muted">
                    <FaIcon icon="fa-solid fa-video-slash" class="mb-2 text-2xl opacity-50" />
                    <p>{{ isOwnProfile ? 'Aún no has subido videos a Vidu.' : 'Este usuario no tiene videos Vidu visibles.' }}</p>
                    <Link v-if="isOwnProfile" href="/vidu/crear" class="mt-3 inline-block text-fb-link hover:underline">
                        Subir tu primer video
                    </Link>
                </div>

                <div v-if="videos.data.length" class="border-t border-fb-border p-4">
                    <ProfilePagination :paginator="videos" />
                </div>
            </div>
        </template>
    </GofioLayout>
</template>
