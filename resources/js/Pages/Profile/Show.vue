<script setup>
/**
 * Perfil público de un usuario con publicaciones, medallas, seguidores y panel de información.
 */

import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import GofioLayout from '@/Layouts/GofioLayout.vue';
import SeoHead from '@/Components/SEO/SeoHead.vue';
import VerificationBadge from '@/Components/User/VerificationBadge.vue';
import RankBadge from '@/Components/User/RankBadge.vue';
import MedalGrid from '@/Components/User/MedalGrid.vue';
import AwardList from '@/Components/User/AwardList.vue';
import ProfileInfoPanel from '@/Components/Profile/ProfileInfoPanel.vue';
import FollowButton from '@/Components/Profile/FollowButton.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import PrioritySupportModal from '@/Components/CreatorPlus/PrioritySupportModal.vue';
import ProfileVisitsModal from '@/Components/CreatorPlus/ProfileVisitsModal.vue';

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
    isOwnProfile: {
        type: Boolean,
        default: false,
    },
    canPrioritySupport: {
        type: Boolean,
        default: false,
    },
    canViewProfileVisits: {
        type: Boolean,
        default: false,
    },
    isCreatorPlus: {
        type: Boolean,
        default: false,
    },
    seo: {
        type: Object,
        default: null,
    },
    recentPosts: {
        type: Array,
        default: () => [],
    },
    postsTotal: {
        type: Number,
        default: 0,
    },
    recentVidu: {
        type: Array,
        default: () => [],
    },
    viduTotal: {
        type: Number,
        default: 0,
    },
});

const profileData = ref({ ...props.profile });
const showPrioritySupport = ref(false);
const showProfileVisits = ref(false);


/** Propaga al perfil el nuevo estado de seguimiento y contadores. */
const onFollowUpdate = (stats) => {
    profileData.value = {
        ...profileData.value,
        ...stats,
    };
};


/** Formatea una fecha ISO en formato local español. */
const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <!-- Perfil público con publicaciones y datos del usuario -->

    <SeoHead :seo="seo" />
    <GofioLayout>
        <template v-if="isOwnProfile && (canPrioritySupport || canViewProfileVisits)" #sidebar>
            <div class="creator-plus-profile-sidebar gofio-box overflow-hidden">
                <div class="creator-plus-profile-sidebar__header">
                    <FaIcon icon="fa-solid fa-crown" />
                    <span>Creator Plus</span>
                </div>
                <div class="creator-plus-profile-sidebar__body">
                    <button
                        v-if="canPrioritySupport"
                        type="button"
                        class="creator-plus-profile-btn"
                        @click="showPrioritySupport = true"
                    >
                        <FaIcon icon="fa-solid fa-headset" />
                        Soporte prioritario
                    </button>
                    <button
                        v-if="canViewProfileVisits"
                        type="button"
                        class="creator-plus-profile-btn"
                        @click="showProfileVisits = true"
                    >
                        <FaIcon icon="fa-solid fa-eye" />
                        Visitas al perfil
                    </button>
                </div>
            </div>
        </template>

        <template #feed>
            <div class="gofio-box overflow-hidden">
                <div
                    class="profile-timeline-cover"
                    :style="profileData.banner_url
                        ? {
                            backgroundImage: `url(${profileData.banner_url})`,
                            backgroundPosition: `${profileData.banner_offset_x ?? 50}% ${profileData.banner_offset_y ?? 50}%`,
                          }
                        : {}"
                />

                <div class="relative px-3 pb-4 pt-0 sm:px-4">
                    <div class="profile-timeline-avatar">
                        <img v-if="profileData.avatar_url" :src="profileData.avatar_url" alt="" class="h-full w-full object-cover" />
                        <span v-else>{{ profileData.username.charAt(0).toUpperCase() }}</span>
                    </div>

                    <div class="profile-timeline-head">
                        <div class="min-w-0 flex-1">
                            <h1 class="profile-username-title">
                                <span>{{ profileData.username }}</span>
                                <VerificationBadge :tipo="profileData.tipo_verificacion" size="title" />
                            </h1>
                            <p v-if="profileData.nick" class="text-sm text-fb-muted">@{{ profileData.nick }}</p>
                            <div class="profile-meta-row">
                                <RankBadge :rango="profileData.rango" size="lg" shine />
                                <span class="profile-meta-karma">{{ profileData.karma }} karma</span>
                                <span v-if="profileData.created_at" class="profile-meta-date">
                                    Miembro desde {{ formatDate(profileData.created_at) }}
                                </span>
                            </div>
                        </div>

                        <div class="profile-timeline-actions">
                            <template v-if="isOwnProfile">
                                <Link href="/configuracion/perfil" class="gofio-btn-primary profile-action-btn">
                                    <FaIcon icon="fa-solid fa-pen-to-square" />
                                    Editar perfil
                                </Link>
                                <Link href="/configuracion/verificacion" class="gofio-btn-secondary profile-action-btn">
                                    Verificación
                                </Link>
                            </template>
                            <FollowButton
                                v-else
                                block
                                :username="profileData.username"
                                :is-following="profileData.is_following"
                                :followers-count="profileData.followers_count ?? 0"
                                @update="onFollowUpdate"
                            />
                        </div>
                    </div>

                    <ProfileInfoPanel :profile="profileData" />
                </div>

                <div class="border-t border-fb-border bg-[#F5F6F7] px-4">
                    <nav class="flex gap-1">
                        <button class="border-b-2 border-brandColor px-4 py-2 text-sm font-semibold text-brandColor">
                            Información
                        </button>
                    </nav>
                </div>
            </div>

            <div
                v-if="isOwnProfile && (canPrioritySupport || canViewProfileVisits)"
                class="creator-plus-profile-sidebar gofio-box mt-3 overflow-hidden lg:hidden"
            >
                <div class="creator-plus-profile-sidebar__header">
                    <FaIcon icon="fa-solid fa-crown" />
                    <span>Creator Plus</span>
                </div>
                <div class="creator-plus-profile-sidebar__body">
                    <button
                        v-if="canPrioritySupport"
                        type="button"
                        class="creator-plus-profile-btn"
                        @click="showPrioritySupport = true"
                    >
                        <FaIcon icon="fa-solid fa-headset" />
                        Soporte prioritario
                    </button>
                    <button
                        v-if="canViewProfileVisits"
                        type="button"
                        class="creator-plus-profile-btn"
                        @click="showProfileVisits = true"
                    >
                        <FaIcon icon="fa-solid fa-eye" />
                        Visitas al perfil
                    </button>
                </div>
            </div>

            <div class="gofio-box mt-4 p-3 sm:p-4">
                <h2 class="mb-3 text-base font-semibold sm:text-lg">Estadísticas</h2>
                <div class="profile-stats-grid">
                    <div class="profile-stat-card">
                        <p class="profile-stat-card__value">{{ profileData.karma }}</p>
                        <p class="profile-stat-card__label">Karma</p>
                    </div>
                    <div class="profile-stat-card">
                        <p class="profile-stat-card__value">{{ profileData.followers_count ?? 0 }}</p>
                        <p class="profile-stat-card__label">Seguidores</p>
                    </div>
                    <div class="profile-stat-card">
                        <p class="profile-stat-card__value">{{ profileData.posts_count }}</p>
                        <p class="profile-stat-card__label">Posts</p>
                    </div>
                    <div class="profile-stat-card">
                        <p class="profile-stat-card__value">{{ profileData.comments_count }}</p>
                        <p class="profile-stat-card__label">Comentarios</p>
                    </div>
                    <div class="profile-stat-card profile-stat-card--wide">
                        <p class="profile-stat-card__value">{{ profileData.medals?.length ?? 0 }}</p>
                        <p class="profile-stat-card__label">Medallas</p>
                    </div>
                </div>
            </div>

            <div class="profile-achievements-grid mt-4">
                <MedalGrid :medals="profileData.medals ?? []" />
                <AwardList :awards="profileData.awards ?? []" />
            </div>

            <!-- Contenedores de posts y Vidu del perfil -->
            <div class="profile-content-grid mt-4">
                <!-- Publicaciones del usuario -->
                <section class="gofio-box profile-content-panel overflow-hidden">
                    <div class="profile-content-section-header">
                        <FaIcon icon="fa-solid fa-newspaper" />
                        <span>Publicaciones</span>
                        <span v-if="postsTotal" class="profile-content-badge">{{ postsTotal }}</span>
                    </div>

                    <ul v-if="recentPosts.length" class="profile-posts-list profile-posts-list--compact">
                        <li v-for="post in recentPosts" :key="post.id" class="profile-posts-list__item">
                            <Link :href="`/post/${post.slug}`" class="profile-posts-list__link profile-posts-list__link--compact">
                                <FaIcon icon="fa-solid fa-file-lines" class="profile-posts-list__icon" />
                                <div class="min-w-0 flex-1">
                                    <p class="profile-posts-list__title">{{ post.title }}</p>
                                    <p class="profile-posts-list__meta">{{ post.created_at_human }}</p>
                                </div>
                                <FaIcon icon="fa-solid fa-chevron-right" class="profile-posts-list__arrow" />
                            </Link>
                        </li>
                    </ul>

                    <div v-else class="profile-content-empty">
                        <FaIcon icon="fa-solid fa-inbox" />
                        <p>{{ isOwnProfile ? 'Aún no has publicado.' : 'Sin publicaciones visibles.' }}</p>
                    </div>

                    <div v-if="postsTotal > recentPosts.length" class="profile-content-footer">
                        <Link :href="`/perfil/${profileData.username}/posts`" class="profile-content-more">
                            Ver más
                            <FaIcon icon="fa-solid fa-arrow-right" />
                        </Link>
                    </div>
                </section>

                <!-- Videos Vidu del usuario -->
                <section class="gofio-box profile-content-panel overflow-hidden">
                    <div class="profile-content-section-header profile-content-section-header--vidu">
                        <FaIcon icon="fa-solid fa-film" />
                        <span>Vidu</span>
                        <span v-if="viduTotal" class="profile-content-badge profile-content-badge--vidu">{{ viduTotal }}</span>
                    </div>

                    <ul v-if="recentVidu.length" class="profile-vidu-preview-list">
                        <li v-for="video in recentVidu" :key="video.id">
                            <Link :href="`/vidu/${video.id}`" class="profile-vidu-preview-item">
                                <div class="profile-vidu-preview-item__thumb">
                                    <img
                                        v-if="video.thumbnail_url"
                                        :src="video.thumbnail_url"
                                        :alt="video.title"
                                        loading="lazy"
                                    />
                                    <div v-else class="profile-vidu-preview-item__placeholder">
                                        <FaIcon icon="fa-solid fa-play" />
                                    </div>
                                    <span class="profile-vidu-preview-item__duration">{{ video.duration_label }}</span>
                                </div>
                                <div class="profile-vidu-preview-item__body">
                                    <p class="profile-vidu-preview-item__title">{{ video.title }}</p>
                                    <p class="profile-vidu-preview-item__meta">
                                        <span><FaIcon icon="fa-solid fa-heart" /> {{ video.likes_count }}</span>
                                        <span>{{ video.created_at_human }}</span>
                                    </p>
                                </div>
                            </Link>
                        </li>
                    </ul>

                    <div v-else class="profile-content-empty">
                        <FaIcon icon="fa-solid fa-video-slash" />
                        <p>{{ isOwnProfile ? 'Aún no has subido videos.' : 'Sin videos Vidu.' }}</p>
                    </div>

                    <div v-if="viduTotal > recentVidu.length" class="profile-content-footer">
                        <Link :href="`/perfil/${profileData.username}/vidu`" class="profile-content-more profile-content-more--vidu">
                            Ver más
                            <FaIcon icon="fa-solid fa-arrow-right" />
                        </Link>
                    </div>
                </section>
            </div>

            <div v-if="!profileData.medals?.length && !profileData.awards?.length && !recentPosts.length && !recentVidu.length" class="gofio-box mt-4 p-8 text-center">
                <FaIcon icon="fa-solid fa-medal" class="mb-3 text-4xl text-fb-muted" />
                <h2 class="text-lg font-semibold">
                    {{ isOwnProfile ? 'Tu perfil' : `Perfil de ${profileData.username}` }}
                </h2>
                <p class="mt-2 text-sm text-fb-muted">
                    Participa en la comunidad para ganar medallas y subir de rango automáticamente.
                </p>
            </div>
        </template>

        <PrioritySupportModal v-if="showPrioritySupport" @close="showPrioritySupport = false" />
        <ProfileVisitsModal v-if="showProfileVisits" @close="showProfileVisits = false" />
    </GofioLayout>
</template>

<style scoped>
.profile-timeline-cover {
    height: 8rem;
    background: linear-gradient(135deg, var(--color-brand), var(--color-brand-hover));
    background-size: cover;
    background-position: center;
}

@media (min-width: 640px) {
    .profile-timeline-cover {
        height: 12rem;
    }
}

.profile-timeline-avatar {
    display: flex;
    height: 5rem;
    width: 5rem;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-top: -2.5rem;
    margin-bottom: 0.75rem;
    border-radius: 0.5rem;
    border: 3px solid white;
    background: #dadde1;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-muted);
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12);
}

@media (min-width: 640px) {
    .profile-timeline-avatar {
        height: 6rem;
        width: 6rem;
        margin-top: -3rem;
        border-width: 4px;
        font-size: 1.75rem;
    }
}

.profile-timeline-head {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 0.875rem;
}

@media (min-width: 640px) {
    .profile-timeline-head {
        flex-direction: row;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
    }
}

.profile-timeline-actions {
    display: flex;
    flex-direction: column;
    width: 100%;
    gap: 0.5rem;
}

@media (min-width: 640px) {
    .profile-timeline-actions {
        flex-direction: row;
        flex-wrap: wrap;
        width: auto;
        max-width: 100%;
    }
}

.profile-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    width: 100%;
    padding-top: 0.625rem;
    padding-bottom: 0.625rem;
    font-size: 0.8125rem;
    line-height: 1.2;
    text-align: center;
}

@media (min-width: 640px) {
    .profile-action-btn {
        width: auto;
    }
}

.profile-username-title {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.25;
    word-break: break-word;
}

@media (min-width: 640px) {
    .profile-username-title {
        font-size: 1.5rem;
    }
}

.profile-meta-row {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.4rem;
    margin-top: 0.5rem;
}

@media (min-width: 640px) {
    .profile-meta-row {
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 0.75rem;
    }
}

.profile-meta-karma {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-muted);
}

.profile-meta-date {
    font-size: 0.6875rem;
    line-height: 1.35;
    color: var(--color-muted);
}

@media (min-width: 640px) {
    .profile-meta-date {
        font-size: 0.75rem;
    }
}

.profile-stats-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}

@media (min-width: 640px) {
    .profile-stats-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }
}

@media (min-width: 1024px) {
    .profile-stats-grid {
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }
}

.profile-stat-card {
    border: 1px solid var(--color-border);
    border-radius: 0.5rem;
    background: #f5f6f7;
    padding: 0.75rem 0.5rem;
    text-align: center;
}

.profile-stat-card--wide {
    grid-column: span 2;
}

@media (min-width: 640px) {
    .profile-stat-card--wide {
        grid-column: span 1;
    }

    .profile-stat-card {
        padding: 0.75rem;
    }
}

.profile-stat-card__value {
    font-size: 1.125rem;
    font-weight: 700;
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
}

@media (min-width: 640px) {
    .profile-stat-card__value {
        font-size: 1.25rem;
    }
}

.profile-stat-card__label {
    margin-top: 0.15rem;
    font-size: 0.6875rem;
    color: var(--color-muted);
}

.profile-achievements-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1rem;
    overflow: visible;
}

@media (min-width: 1024px) {
    .profile-achievements-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: start;
    }
}

@media (min-width: 640px) {
    .profile-stat-card__label {
        font-size: 0.75rem;
    }
}

.creator-plus-profile-sidebar {
    position: sticky;
    top: 5rem;
    border: 1px solid rgba(251, 191, 36, 0.35);
    box-shadow: 0 8px 24px rgba(217, 119, 6, 0.1);
}

.creator-plus-profile-sidebar__header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #92400e, #d97706);
    padding: 0.65rem 0.85rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: white;
}

.creator-plus-profile-sidebar__body {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 0.75rem;
    background: linear-gradient(180deg, #fffbeb, #ffffff);
}

.creator-plus-profile-btn {
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: flex-start;
    gap: 0.5rem;
    border-radius: 0.5rem;
    border: 1px solid #fbbf24;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    padding: 0.55rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #92400e;
    transition: box-shadow 0.15s ease, transform 0.15s ease;
}

.creator-plus-profile-btn:hover {
    box-shadow: 0 0 0 2px rgba(251, 191, 36, 0.35);
    transform: translateY(-1px);
}
</style>
