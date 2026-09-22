<script setup>
/**
 * Vista individual de un video Vidu (para compartir en redes sociales).
 */

import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import ViduPlayer from '@/Components/Vidu/ViduPlayer.vue';
import SeoHead from '@/Components/SEO/SeoHead.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';

const props = defineProps({
    video: {
        type: Object,
        required: true,
    },
    canLike: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const globalMuted = ref(true);

const seo = {
    title: props.video.title || `Video de @${props.video.user?.username} en Gofio`,
    description: props.video.description || '¡Mira este video en Gofio!',
    image: props.video.thumbnail_url,
    canonical: `${window?.location?.origin || ''}/vidu/${props.video.id}`,
};

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
    if (!props.canLike) return;
    await apiPost(`/api/vidu/${video.id}/like`);
};

const handleSave = async (video) => {
    if (!props.canLike) return;
    await apiPost(`/api/vidu/${video.id}/save`);
};

const handleView = async (video) => {
    await apiPost(`/api/vidu/${video.id}/view`);
};
</script>

<template>
    <SeoHead :seo="seo" />
    <div class="vidu-page">
        <header class="vidu-header">
            <Link href="/vidu" class="vidu-header-back">
                <FaIcon icon="fa-solid fa-chevron-left" />
            </Link>
            <h1 class="vidu-header-title">
                <FaIcon icon="fa-solid fa-film" class="mr-1" />
                Vidu
            </h1>
            <Link href="/vidu/crear" class="vidu-header-create">
                <FaIcon icon="fa-solid fa-plus" />
            </Link>
        </header>

        <main class="vidu-feed">
            <ViduPlayer
                :video="video"
                :global-muted="globalMuted"
                @like="handleLike"
                @save="handleSave"
                @view="handleView"
                @update:globalMuted="globalMuted = $event"
            />
        </main>
    </div>
</template>
