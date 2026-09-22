<script setup>
/**
 * Feed Vidu: scroll vertical tipo Reels con snap, autoplay por IntersectionObserver
 * y carga infinita de videos al llegar al último elemento.
 */

import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import ViduPlayer from '@/Components/Vidu/ViduPlayer.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import SiteBrand from '@/Components/UI/SiteBrand.vue';

const props = defineProps({
    initialVideos: {
        type: Array,
        default: () => [],
    },
    hasMore: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const videos = ref([...props.initialVideos]);
const hasMore = ref(props.hasMore);
const currentPage = ref(1);
const loading = ref(false);
const error = ref('');
const globalMuted = ref(true);
const activeTab = ref('feed'); // 'feed' | 'saved' | 'mine'

// ---------- Carga de más videos (infinite scroll) ----------
const loadMore = async () => {
    if (loading.value || !hasMore.value) return;
    loading.value = true;
    error.value = '';

    try {
        const nextPage = currentPage.value + 1;
        const endpoint = activeTab.value === 'saved'
            ? `/api/vidu/saved?page=${nextPage}`
            : activeTab.value === 'mine'
                ? `/api/vidu/mine?page=${nextPage}`
                : `/api/vidu/feed?page=${nextPage}`;

        const res = await fetch(endpoint, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!res.ok) throw new Error('Error al cargar más videos.');
        const data = await res.json();

        videos.value.push(...(data.videos || []));
        hasMore.value = data.has_more ?? false;
        currentPage.value = nextPage;
    } catch {
        error.value = 'No se pudieron cargar más videos.';
    } finally {
        loading.value = false;
    }
};

// Detecta cuando el usuario llega al último video y carga más
let endObserver = null;
const endSentinel = ref(null);

onMounted(() => {
    endObserver = new IntersectionObserver(
        ([entry]) => { if (entry.isIntersecting) loadMore(); },
        { rootMargin: '200px' },
    );
    if (endSentinel.value) endObserver.observe(endSentinel.value);
});

onBeforeUnmount(() => endObserver?.disconnect());

// ---------- Cambio de pestaña ----------
const switchTab = async (tab) => {
    if (tab === activeTab.value) return;
    activeTab.value = tab;
    currentPage.value = 0;
    hasMore.value = true;
    videos.value = [];
    await nextTick();
    await loadMore();
};

// ---------- Acciones sobre videos ----------
const apiPost = async (url) => {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': page.props.csrf_token,
            Accept: 'application/json',
        },
        credentials: 'same-origin',
    });
    return res.json();
};

const handleLike = async (video) => {
    const data = await apiPost(`/api/vidu/${video.id}/like`);
    const found = videos.value.find((v) => v.id === video.id);
    if (found && data) {
        found.liked_by_user = data.liked;
        found.likes_count = data.likes_count;
    }
};

const handleSave = async (video) => {
    const data = await apiPost(`/api/vidu/${video.id}/save`);
    const found = videos.value.find((v) => v.id === video.id);
    if (found && data) {
        found.saved_by_user = data.saved;
        found.saves_count = data.saves_count;
    }
};

const handleView = async (video) => {
    await apiPost(`/api/vidu/${video.id}/view`);
};

const handleShare = () => {}; // Gestionado dentro del player

const handleDelete = async (video) => {
    const res = await fetch(`/api/vidu/${video.id}`, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': page.props.csrf_token,
        },
        credentials: 'same-origin',
    });
    if (res.ok) {
        videos.value = videos.value.filter((v) => v.id !== video.id);
    }
};
</script>

<template>
    <!-- Layout Vidu: pantalla completa sin sidebars -->
    <div class="vidu-page">
        <!-- Header minimal -->
        <header class="vidu-header">
            <Link href="/" class="vidu-header-back" title="Volver al inicio">
                <FaIcon icon="fa-solid fa-chevron-left" />
            </Link>
            <h1 class="vidu-header-title">
                <FaIcon icon="fa-solid fa-film" class="mr-1" />
                Vidu
            </h1>
            <Link href="/vidu/crear" class="vidu-header-create" title="Subir video">
                <FaIcon icon="fa-solid fa-plus" />
            </Link>
        </header>

        <!-- Pestañas de filtro -->
        <nav class="vidu-tabs">
            <button
                class="vidu-tab"
                :class="{ 'vidu-tab--active': activeTab === 'feed' }"
                @click="switchTab('feed')"
            >
                Para ti
            </button>
            <button
                class="vidu-tab"
                :class="{ 'vidu-tab--active': activeTab === 'saved' }"
                @click="switchTab('saved')"
            >
                Guardados
            </button>
            <button
                class="vidu-tab"
                :class="{ 'vidu-tab--active': activeTab === 'mine' }"
                @click="switchTab('mine')"
            >
                Mis videos
            </button>
        </nav>

        <!-- Feed: contenedor con scroll-snap vertical -->
        <main class="vidu-feed" id="vidu-feed">
            <!-- Estado vacío -->
            <div v-if="videos.length === 0 && !loading" class="vidu-empty">
                <FaIcon icon="fa-solid fa-video-slash" class="vidu-empty-icon" />
                <p v-if="activeTab === 'feed'">Aún no hay videos. ¡Sé el primero!</p>
                <p v-else-if="activeTab === 'saved'">No tienes videos guardados.</p>
                <p v-else>No has subido videos aún.</p>
                <Link v-if="activeTab !== 'feed'" href="/vidu" class="vidu-link-btn" @click="switchTab('feed')">Ver feed</Link>
                <Link href="/vidu/crear" class="vidu-link-btn">Subir video</Link>
            </div>

            <!-- Cards de video -->
            <ViduPlayer
                v-for="video in videos"
                :key="video.id"
                :video="video"
                :global-muted="globalMuted"
                @like="handleLike"
                @save="handleSave"
                @share="handleShare"
                @view="handleView"
                @delete="handleDelete"
                @update:globalMuted="globalMuted = $event"
            />

            <!-- Sentinel de carga infinita -->
            <div ref="endSentinel" class="vidu-sentinel" />

            <!-- Spinner de carga -->
            <div v-if="loading" class="vidu-loader">
                <FaIcon icon="fa-solid fa-circle-notch" class="vidu-loader-icon" />
            </div>

            <!-- Error -->
            <p v-if="error" class="vidu-error">{{ error }}</p>
        </main>
    </div>
</template>
