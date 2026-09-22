<script setup>
/**
 * Reproductor Vidu: video vertical estilo Reels con Plyr como motor de reproducción.
 * Soporta autoplay por IntersectionObserver, controles táctiles y barra lateral de acciones.
 */

import Plyr from 'plyr';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import FaIcon from '@/Components/UI/FaIcon.vue';
import RankLabel from '@/Components/User/RankLabel.vue';

const props = defineProps({
    video: {
        type: Object,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    globalMuted: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['like', 'save', 'share', 'delete', 'view', 'update:globalMuted']);

// ---------- refs DOM / Plyr ----------
const cardRef = ref(null);
const videoEl = ref(null);
const adVideoEl = ref(null);
let player = null;
let observer = null;
let viewTimer = null;
let adLoadTimer = null;

// ---------- estado reactivo ----------
const isPlaying = ref(false);
const isMuted = ref(props.globalMuted);
const showFlash = ref(false);
const flashIcon = ref('fa-play');
const showSeekLeft = ref(false);
const showSeekRight = ref(false);
const progressPercent = ref(0);
const localLiked = ref(props.video.liked_by_user ?? false);
const localLikesCount = ref(props.video.likes_count ?? 0);
const localSaved = ref(props.video.saved_by_user ?? false);
const localSavesCount = ref(props.video.saves_count ?? 0);
const shareSuccess = ref(false);
const showDeleteConfirm = ref(false);

// ---------- pausa publicitaria ----------
const adBreakPlayed = ref(false);
const adPhase = ref(null); // null | 'preparing' | 'playing' | 'finishing'
const showAdPreparing = ref(false);
const showAdOverlay = ref(false);
const savedMainTime = ref(0);
const adVideoUrl = ref(null);
const adCountdown = ref(null);
const adProgressPercent = ref(0);
let adProgressHandler = null;
let adFinishGuard = false;

/** Segundos de aviso previo antes de la pausa publicitaria. */
const AD_COUNTDOWN_SECONDS = 5;

/** Bloquea controles del reel mientras se prepara o reproduce la publicidad. */
const isAdLocked = computed(() => adPhase.value !== null);

/** Duración configurada del creativo publicitario activo. */
const adConfiguredDuration = computed(() => (
    Math.max(1, Number(props.video.ad_break?.creative?.duration_seconds) || 1)
));

/** Referencia al elemento media nativo del video principal. */
const mainMedia = () => videoEl.value ?? player?.media ?? null;

/** Reinicia el estado de publicidad al cambiar de video en el feed. */
const resetAdBreakState = () => {
    detachAdProgressHandler();
    adBreakPlayed.value = false;
    adPhase.value = null;
    showAdPreparing.value = false;
    showAdOverlay.value = false;
    savedMainTime.value = 0;
    adVideoUrl.value = null;
    adCountdown.value = null;
    adProgressPercent.value = 0;
    adFinishGuard = false;
    clearTimeout(adLoadTimer);
    adVideoEl.value?.pause();
};

/** Desvincula el listener de progreso del video publicitario. */
const detachAdProgressHandler = () => {
    if (adVideoEl.value && adProgressHandler) {
        adVideoEl.value.removeEventListener('timeupdate', adProgressHandler);
    }
    adProgressHandler = null;
};

/** Actualiza la cuenta regresiva previa y dispara la pausa en el instante configurado. */
const checkAdBreakCue = () => {
    if (!player || adBreakPlayed.value || isAdLocked.value || !props.video.ad_break) {
        return;
    }

    const media = mainMedia();
    const currentTime = media?.currentTime ?? player.currentTime;
    const cueAt = props.video.ad_break.cue_at_seconds;
    const remaining = cueAt - currentTime;

    if (currentTime >= cueAt) {
        adCountdown.value = null;
        startAdBreak();
        return;
    }

    if (remaining > 0 && remaining <= AD_COUNTDOWN_SECONDS) {
        adCountdown.value = Math.ceil(remaining);
        return;
    }

    adCountdown.value = null;
};

/** Pausa el video original y prepara la reproducción del anuncio. */
const startAdBreak = () => {
    const creative = props.video.ad_break?.creative;
    const media = mainMedia();

    if (!player || !media || !creative?.video_url || adBreakPlayed.value) {
        return;
    }

    adBreakPlayed.value = true;
    adPhase.value = 'preparing';
    adCountdown.value = null;
    savedMainTime.value = media.currentTime;
    player.pause();
    clearTimeout(viewTimer);

    showAdPreparing.value = true;
    adVideoUrl.value = creative.video_url;

    clearTimeout(adLoadTimer);
    adLoadTimer = setTimeout(() => abortAdBreak('timeout'), 15000);

    nextTick(() => {
        adVideoEl.value?.load();
    });
};

/** Reproduce el anuncio cuando el archivo está listo. */
const playAdVideo = async () => {
    if (!adVideoEl.value || adPhase.value !== 'preparing') {
        return;
    }

    clearTimeout(adLoadTimer);
    showAdPreparing.value = false;
    showAdOverlay.value = true;
    adPhase.value = 'playing';
    adProgressPercent.value = 0;

    adVideoEl.value.currentTime = 0;
    adVideoEl.value.muted = isMuted.value;
    adVideoEl.value.loop = false;

    detachAdProgressHandler();
    adProgressHandler = () => {
        if (!adVideoEl.value || adPhase.value !== 'playing') {
            return;
        }

        // Respeta la duración configurada en el panel admin.
        const elapsed = adVideoEl.value.currentTime;
        const total = adConfiguredDuration.value;
        adProgressPercent.value = Math.min(100, (elapsed / total) * 100);

        if (elapsed >= total) {
            completeAdBreak();
        }
    };
    adVideoEl.value.addEventListener('timeupdate', adProgressHandler);

    try {
        await adVideoEl.value.play();
    } catch {
        abortAdBreak('play_failed');
    }
};

/** Reanuda el video original en el instante exacto previo a la publicidad. */
const resumeMainVideo = () => {
    const media = mainMedia();

    if (!player || !media) {
        adPhase.value = null;
        adFinishGuard = false;
        return;
    }

    const targetTime = savedMainTime.value;

    const finishResume = () => {
        player.muted = isMuted.value;
        if (player.duration > 0) {
            progressPercent.value = (targetTime / player.duration) * 100;
        }
        player.play().catch(() => {});
        adPhase.value = null;
        adFinishGuard = false;
    };

    if (Math.abs(media.currentTime - targetTime) < 0.05) {
        finishResume();
        return;
    }

    const seekTimeout = setTimeout(() => {
        if (adPhase.value === 'finishing') {
            finishResume();
        }
    }, 600);

    media.addEventListener('seeked', () => {
        clearTimeout(seekTimeout);
        finishResume();
    }, { once: true });

    media.currentTime = targetTime;
    player.currentTime = targetTime;
};

/** Finaliza la publicidad y continúa el reel donde se pausó. */
const completeAdBreak = () => {
    if (adFinishGuard || adPhase.value === 'finishing' || adPhase.value === null) {
        return;
    }

    adFinishGuard = true;
    adPhase.value = 'finishing';

    detachAdProgressHandler();
    clearTimeout(adLoadTimer);
    showAdPreparing.value = false;
    showAdOverlay.value = false;
    adProgressPercent.value = 0;

    if (adVideoEl.value) {
        adVideoEl.value.pause();
        adVideoEl.value.removeAttribute('src');
        adVideoEl.value.load();
    }

    adVideoUrl.value = null;
    resumeMainVideo();
};

/** Cancela la publicidad ante errores sin reiniciar el reel desde cero. */
const abortAdBreak = () => {
    if (adPhase.value === null || adPhase.value === 'finishing') {
        return;
    }

    completeAdBreak();
};

/** Arranca el anuncio en cuanto el archivo publicitario puede reproducirse. */
const onAdLoaded = () => {
    if (adPhase.value === 'preparing' && !showAdOverlay.value) {
        playAdVideo();
    }
};

/** Ignora errores del anuncio si ya terminó o aún no empezó la reproducción. */
const onAdError = () => {
    if (adPhase.value === 'preparing' || adPhase.value === 'playing') {
        abortAdBreak();
    }
};

/** Respaldo por fin natural del archivo (más corto que la duración configurada). */
const onAdEnded = () => {
    if (adPhase.value === 'playing') {
        completeAdBreak();
    }
};

const formattedDuration = computed(() => {
    const s = props.video.duration_seconds || 0;
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return `${m}:${sec.toString().padStart(2, '0')}`;
});

/** Ruta pública del perfil del autor del video. */
const authorProfileUrl = computed(() => (
    props.video.user?.username ? `/perfil/${props.video.user.username}` : null
));

const formatCount = (n) => {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
    if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K';
    return String(n);
};

// ---------- Plyr lifecycle ----------
onMounted(async () => {
    await nextTick();

    if (!videoEl.value) return;

    // Plyr en modo minimal: sin controles propios (usamos los nuestros)
    player = new Plyr(videoEl.value, {
        controls: [],
        autopause: false,
        clickToPlay: false,
        keyboard: { focused: false, global: false },
        tooltips: { controls: false, seek: false },
        storage: { enabled: false },
        loop: { active: true },
    });

    player.muted = isMuted.value;
    player.volume = 1;

    player.on('play', () => { isPlaying.value = true; });
    player.on('pause', () => { isPlaying.value = false; });
    player.on('timeupdate', () => {
        if (player.duration > 0 && adPhase.value === null) {
            progressPercent.value = (player.currentTime / player.duration) * 100;
        }
        checkAdBreakCue();
    });

    // IntersectionObserver: durante publicidad solo controla el anuncio, no el reel principal.
    observer = new IntersectionObserver(
        ([entry]) => {
            if (isAdLocked.value) {
                if (adPhase.value === 'playing' && adVideoEl.value) {
                    if (entry.isIntersecting && entry.intersectionRatio >= 0.6) {
                        adVideoEl.value.play().catch(() => {});
                    } else {
                        adVideoEl.value.pause();
                    }
                }
                return;
            }

            if (entry.isIntersecting && entry.intersectionRatio >= 0.6) {
                playVideo();
            } else {
                pauseVideo();
            }
        },
        { threshold: [0, 0.6] },
    );

    if (cardRef.value) observer.observe(cardRef.value);
});

onBeforeUnmount(() => {
    clearTimeout(viewTimer);
    clearTimeout(adLoadTimer);
    detachAdProgressHandler();
    observer?.disconnect();
    player?.destroy();
});

watch(() => props.video.id, () => {
    resetAdBreakState();
});

// Sincroniza mute global
watch(() => props.globalMuted, (val) => {
    isMuted.value = val;
    if (player) player.muted = val;
    if (adVideoEl.value) adVideoEl.value.muted = val;
});

// ---------- Control de reproducción ----------
const playVideo = () => {
    if (!player || isAdLocked.value) return;
    player.muted = isMuted.value;
    player.play().catch(() => {
        // Algunos navegadores bloquean autoplay sin interacción: ignorar el error
    });
    // Registra vista tras 3 segundos de reproducción
    clearTimeout(viewTimer);
    viewTimer = setTimeout(() => emit('view', props.video), 3000);
};

const pauseVideo = () => {
    if (!player || isAdLocked.value) return;
    player.pause();
    clearTimeout(viewTimer);
};

const togglePlay = () => {
    if (!player || isAdLocked.value) return;
    if (player.paused) {
        player.play();
        flashIcon.value = 'fa-play';
    } else {
        player.pause();
        flashIcon.value = 'fa-pause';
    }
    showFlash.value = true;
    setTimeout(() => { showFlash.value = false; }, 700);
};

// ---------- Seek con zonas táctiles ----------
const seekBack = (e) => {
    e.stopPropagation();
    if (!player || isAdLocked.value) return;
    player.currentTime = Math.max(0, player.currentTime - 5);
    showSeekLeft.value = true;
    setTimeout(() => { showSeekLeft.value = false; }, 600);
};

const seekForward = (e) => {
    e.stopPropagation();
    if (!player || isAdLocked.value) return;
    player.currentTime = Math.min(player.duration, player.currentTime + 5);
    showSeekRight.value = true;
    setTimeout(() => { showSeekRight.value = false; }, 600);
};

// Clic en la barra de progreso para hacer seek
const onProgressClick = (e) => {
    if (!player || !player.duration || isAdLocked.value) return;
    const rect = e.currentTarget.getBoundingClientRect();
    const ratio = (e.clientX - rect.left) / rect.width;
    player.currentTime = ratio * player.duration;
};

// ---------- Sonido ----------
const toggleMute = () => {
    isMuted.value = !isMuted.value;
    if (player) player.muted = isMuted.value;
    if (adVideoEl.value) adVideoEl.value.muted = isMuted.value;
    emit('update:globalMuted', isMuted.value);
};

// ---------- Acciones sociales ----------
const onLike = () => {
    localLiked.value = !localLiked.value;
    localLikesCount.value += localLiked.value ? 1 : -1;
    emit('like', props.video);
};

const onSave = () => {
    localSaved.value = !localSaved.value;
    localSavesCount.value += localSaved.value ? 1 : -1;
    emit('save', props.video);
};

const shareVideo = async () => {
    const url = `${window.location.origin}/vidu/${props.video.id}`;
    const title = props.video.title || 'Video en Gofio';
    const text = props.video.description || '¡Mira este video en Gofio!';

    if (navigator.share) {
        try {
            await navigator.share({ title, text, url });
        } catch {
            // Usuario canceló el share nativo
        }
    } else {
        try {
            await navigator.clipboard.writeText(url);
            shareSuccess.value = true;
            setTimeout(() => { shareSuccess.value = false; }, 2000);
        } catch {
            // Fallback: seleccionar texto
        }
    }
    emit('share', props.video);
};

const confirmDelete = () => { showDeleteConfirm.value = true; };
const cancelDelete = () => { showDeleteConfirm.value = false; };
const doDelete = () => {
    showDeleteConfirm.value = false;
    emit('delete', props.video);
};
</script>

<template>
    <!-- Tarjeta de video Vidu: ocupa el viewport completo en el feed -->
    <div ref="cardRef" class="vidu-card">
        <!-- Video nativo controlado por Plyr -->
        <video
            ref="videoEl"
            class="vidu-video"
            :src="video.video_url"
            :poster="video.thumbnail_url || undefined"
            playsinline
            loop
            muted
            preload="none"
        />

        <!-- Aviso previo: cuenta regresiva 5→1 mientras el reel sigue reproduciéndose -->
        <Transition name="vidu-ad-countdown">
            <div v-if="adCountdown !== null && !isAdLocked" class="vidu-ad-countdown-wrap">
                <div class="vidu-ad-countdown">
                    <span class="vidu-ad-countdown-text">Publicidad</span>
                    <span :key="adCountdown" class="vidu-ad-countdown-number">{{ adCountdown }}</span>
                </div>
            </div>
        </Transition>

        <!-- Capa publicitaria: mismo encuadre que el reel + barra de progreso -->
        <div v-if="showAdPreparing || showAdOverlay" class="vidu-ad-layer" @click.stop>
            <video
                ref="adVideoEl"
                v-show="showAdOverlay"
                class="vidu-ad-video"
                :src="adVideoUrl || undefined"
                playsinline
                preload="auto"
                @loadeddata="onAdLoaded"
                @ended="onAdEnded"
                @error="onAdError"
            />
            <!-- Barra amarilla de avance durante la publicidad -->
            <div v-if="showAdOverlay" class="vidu-ad-progress">
                <div class="vidu-ad-progress-fill" :style="{ width: `${adProgressPercent}%` }" />
            </div>
        </div>

        <!-- Zona izquierda: retroceder 5s -->
        <div class="vidu-seek-zone vidu-seek-zone--left" @click.stop="seekBack">
            <Transition name="vidu-seek-anim">
                <div v-if="showSeekLeft" class="vidu-seek-hint">
                    <FaIcon icon="fa-solid fa-backward" />
                    <span>5s</span>
                </div>
            </Transition>
        </div>

        <!-- Centro: toggle play/pause -->
        <div class="vidu-center-zone" @click.stop="togglePlay">
            <Transition name="vidu-flash">
                <div v-if="showFlash" class="vidu-flash-icon">
                    <FaIcon :icon="`fa-solid ${flashIcon}`" />
                </div>
            </Transition>
            <!-- Icono de play permanente cuando está pausado y no hay flash -->
            <div v-if="!isPlaying && !showFlash && !isAdLocked" class="vidu-paused-icon">
                <FaIcon icon="fa-solid fa-play" />
            </div>
        </div>

        <!-- Zona derecha: adelantar 5s -->
        <div class="vidu-seek-zone vidu-seek-zone--right" @click.stop="seekForward">
            <Transition name="vidu-seek-anim">
                <div v-if="showSeekRight" class="vidu-seek-hint">
                    <span>5s</span>
                    <FaIcon icon="fa-solid fa-forward" />
                </div>
            </Transition>
        </div>

        <!-- Info inferior: autor (enlace al perfil), título, descripción, progreso -->
        <div class="vidu-bottom">
            <!-- Autor: avatar y textos enlazan al perfil; el hover visual solo rodea el círculo -->
            <div v-if="authorProfileUrl" class="vidu-author-row">
                <Link
                    :href="authorProfileUrl"
                    class="vidu-avatar-link"
                    :aria-label="`Ver perfil de ${video.user?.username}`"
                    @click.stop
                >
                    <img v-if="video.user?.avatar_url" :src="video.user.avatar_url" class="vidu-avatar" :alt="video.user?.username" />
                    <div v-else class="vidu-avatar vidu-avatar--placeholder">
                        {{ video.user?.username?.[0]?.toUpperCase() }}
                    </div>
                </Link>
                <div class="vidu-author-meta">
                    <Link :href="authorProfileUrl" class="vidu-author-text-link" @click.stop>
                        <p class="vidu-display-name">{{ video.user?.username }}</p>
                        <RankLabel :rango="video.user?.rango" class="vidu-author-rank" />
                        <p class="vidu-username">@{{ video.user?.username }}</p>
                    </Link>
                    <p v-if="video.title" class="vidu-title-text">{{ video.title }}</p>
                </div>
            </div>
            <p v-if="video.description" class="vidu-description">{{ video.description }}</p>
            <span class="vidu-time-badge">{{ formattedDuration }}</span>

            <!-- Barra de progreso (seekable) -->
            <div class="vidu-progress-wrap" @click.stop="onProgressClick">
                <div class="vidu-progress-track">
                    <div class="vidu-progress-fill" :style="{ width: progressPercent + '%' }" />
                </div>
            </div>
        </div>

        <!-- Sidebar derecha: banner publicitario y acciones sociales -->
        <aside class="vidu-sidebar">
            <!-- Banner vertical lateral derecho (rotación aleatoria por video) -->
            <div v-if="video.sidebar_banner && !isAdLocked" class="vidu-sidebar-banner">
                <a
                    v-if="video.sidebar_banner.link_url"
                    :href="video.sidebar_banner.link_url"
                    class="vidu-sidebar-banner-link"
                    target="_blank"
                    rel="noopener noreferrer"
                    @click.stop
                >
                    <img
                        :src="video.sidebar_banner.image_url"
                        :alt="video.sidebar_banner.name || 'Publicidad'"
                        class="vidu-sidebar-banner-img"
                    />
                </a>
                <img
                    v-else
                    :src="video.sidebar_banner.image_url"
                    :alt="video.sidebar_banner.name || 'Publicidad'"
                    class="vidu-sidebar-banner-img"
                />
            </div>

            <!-- Mute / Unmute -->
            <button class="vidu-action-btn" :title="isMuted ? 'Activar sonido' : 'Silenciar'" @click.stop="toggleMute">
                <FaIcon :icon="isMuted ? 'fa-solid fa-volume-xmark' : 'fa-solid fa-volume-high'" class="vidu-action-icon" />
            </button>

            <!-- Like -->
            <button
                class="vidu-action-btn"
                :class="{ 'vidu-action-btn--liked': localLiked }"
                title="Me gusta"
                @click.stop="onLike"
            >
                <FaIcon icon="fa-solid fa-heart" class="vidu-action-icon" />
                <span class="vidu-action-count">{{ formatCount(localLikesCount) }}</span>
            </button>

            <!-- Guardar -->
            <button
                class="vidu-action-btn"
                :class="{ 'vidu-action-btn--saved': localSaved }"
                title="Guardar"
                @click.stop="onSave"
            >
                <FaIcon icon="fa-solid fa-bookmark" class="vidu-action-icon" />
                <span class="vidu-action-count">{{ formatCount(localSavesCount) }}</span>
            </button>

            <!-- Compartir -->
            <button class="vidu-action-btn" :class="{ 'vidu-action-btn--shared': shareSuccess }" title="Compartir" @click.stop="shareVideo">
                <FaIcon icon="fa-solid fa-share-nodes" class="vidu-action-icon" />
                <span class="vidu-action-count">{{ shareSuccess ? '¡Copiado!' : 'Compartir' }}</span>
            </button>

            <!-- Eliminar (solo si es mío) -->
            <button v-if="video.can_delete" class="vidu-action-btn vidu-action-btn--danger" title="Eliminar" @click.stop="confirmDelete">
                <FaIcon icon="fa-solid fa-trash" class="vidu-action-icon" />
            </button>
        </aside>

        <!-- Modal de confirmación de borrado -->
        <Transition name="vidu-modal">
            <div v-if="showDeleteConfirm" class="vidu-delete-confirm" @click.stop>
                <p>¿Eliminar este video?</p>
                <div class="vidu-delete-actions">
                    <button class="vidu-btn-cancel" @click="cancelDelete">Cancelar</button>
                    <button class="vidu-btn-delete" @click="doDelete">Eliminar</button>
                </div>
            </div>
        </Transition>
    </div>
</template>
