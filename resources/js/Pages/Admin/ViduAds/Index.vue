<script setup>
/**
 * Panel de publicidad Vidu Reels: tres secciones organizadas en tabs —
 * Banners Laterales, Pausas de Video y Configuración/Alcance.
 */

import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';

const props = defineProps({
    settings: Object,
    creatives: Array,
    activeCreativesCount: Number,
    banners: Array,
    activeBannersCount: Number,
    videos: Object,
    filters: Object,
    stats: Object,
});

// ─── Tab activo ─────────────────────────────────────────────────────────────
/** Pestañas disponibles: banners, creativos, configuración/alcance. */
const activeTab = ref('banners');

// ─── Formulario de configuración global ─────────────────────────────────────
const settingsForm = useForm({
    enabled: props.settings?.enabled ?? false,
    apply_mode: props.settings?.apply_mode ?? 'all',
    viewer_mode: props.settings?.viewer_mode ?? 'everyone',
    min_video_seconds: props.settings?.min_video_seconds ?? 60,
    trigger_min_seconds: props.settings?.trigger_min_seconds ?? 20,
    trigger_max_percent: props.settings?.trigger_max_percent ?? 75,
    sidebar_banners_enabled: props.settings?.sidebar_banners_enabled ?? false,
});

/** Guarda la configuración global de publicidad. */
const saveSettings = () => {
    settingsForm.put('/admin/vidu-publicidad/configuracion', { preserveScroll: true });
};

// ─── Creativos de video publicitario ────────────────────────────────────────
const creativeForm = useForm({
    name: '',
    video: null,
    duration_seconds: 15,
});

const videoInputRef = ref(null);

const onCreativeSelected = (e) => {
    creativeForm.video = e.target.files?.[0] ?? null;
};

/** Sube un nuevo video publicitario al servidor. */
const uploadCreative = () => {
    if (!creativeForm.video) return;
    creativeForm.post('/admin/vidu-publicidad/creativos', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            creativeForm.reset();
            creativeForm.duration_seconds = 15;
            if (videoInputRef.value) videoInputRef.value.value = '';
        },
    });
};

/** Activa o desactiva un creativo publicitario. */
const toggleCreative = (creative) => {
    router.put(`/admin/vidu-publicidad/creativos/${creative.id}`, {}, { preserveScroll: true });
};

/** Elimina un creativo publicitario. */
const deleteCreative = (creative) => {
    if (!confirm(`¿Eliminar el video "${creative.name}"? Esta acción no se puede deshacer.`)) return;
    router.delete(`/admin/vidu-publicidad/creativos/${creative.id}`, { preserveScroll: true });
};

// ─── Banners laterales ───────────────────────────────────────────────────────
const bannerForm = useForm({
    name: '',
    image: null,
    link_url: '',
});

const bannerInputRef = ref(null);

const onBannerSelected = (e) => {
    bannerForm.image = e.target.files?.[0] ?? null;
};

/** Sube un nuevo banner vertical para el lateral derecho del feed. */
const uploadBanner = () => {
    if (!bannerForm.image) return;
    bannerForm.post('/admin/vidu-publicidad/banners', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            bannerForm.reset();
            if (bannerInputRef.value) bannerInputRef.value.value = '';
        },
    });
};

/** Activa o desactiva un banner lateral. */
const toggleBanner = (banner) => {
    router.put(`/admin/vidu-publicidad/banners/${banner.id}`, {
        is_active: !banner.is_active,
    }, { preserveScroll: true });
};

/** Elimina un banner lateral. */
const deleteBanner = (banner) => {
    if (!confirm(`¿Eliminar el banner "${banner.name}"?`)) return;
    router.delete(`/admin/vidu-publicidad/banners/${banner.id}`, { preserveScroll: true });
};

// ─── Alcance por video (overrides individuales) ───────────────────────────────
const searchQ = ref(props.filters?.q ?? '');
const adsFilter = ref(props.filters?.ads ?? '');

const reloadVideos = () => {
    router.get('/admin/vidu-publicidad', {
        q: searchQ.value || undefined,
        ads: adsFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

/** Cambia la regla de publicidad de un video concreto. */
const setVideoMode = (video, mode) => {
    router.put(`/admin/vidu-publicidad/videos/${video.id}`, { mode }, { preserveScroll: true });
};

/** Acción masiva sobre todos los videos. */
const bulkAction = (action) => {
    const labels = {
        enable_all: 'forzar publicidad en TODOS los videos',
        disable_all: 'quitar publicidad en TODOS los videos',
        reset_all: 'restablecer TODOS los videos a la regla global',
    };
    if (!confirm(`¿Confirmas ${labels[action]}?`)) return;
    router.post('/admin/vidu-publicidad/videos/masivo', { action }, { preserveScroll: true });
};

// ─── Utilidades ──────────────────────────────────────────────────────────────
/** Formatea segundos como mm:ss. */
const fmt = (s) => {
    const m = Math.floor((s || 0) / 60);
    const sec = (s || 0) % 60;
    return `${m}:${sec.toString().padStart(2, '0')}`;
};

const videoAdsLabel = (v) => {
    if (v.ads_override === true) return 'Con publicidad';
    if (v.ads_override === false) return 'Sin publicidad';
    return 'Regla global';
};

const videoAdsClass = (v) => {
    if (v.ads_override === true) return 'text-emerald-700 bg-emerald-50';
    if (v.ads_override === false) return 'text-red-700 bg-red-50';
    return 'text-fb-muted bg-[#F5F6F7]';
};
</script>

<template>
    <!-- Panel de publicidad Vidu Reels -->
    <AdminLayout>
        <div class="space-y-4">

            <!-- Cabecera de estado general -->
            <div class="vidu-ads-header">
                <div class="vidu-ads-header__title">
                    <FaIcon icon="fa-solid fa-rectangle-ad" class="text-brand" />
                    <span>Publicidad Vidu Reels</span>
                </div>
                <div class="vidu-ads-header__stats">
                    <div class="vidu-ads-stat">
                        <span class="vidu-ads-stat__val" :class="settings?.enabled ? 'text-emerald-600' : 'text-red-500'">
                            {{ settings?.enabled ? 'Activo' : 'Inactivo' }}
                        </span>
                        <span class="vidu-ads-stat__lbl">Sistema</span>
                    </div>
                    <div class="vidu-ads-stat">
                        <span class="vidu-ads-stat__val">{{ activeCreativesCount ?? 0 }}</span>
                        <span class="vidu-ads-stat__lbl">Videos activos</span>
                    </div>
                    <div class="vidu-ads-stat">
                        <span class="vidu-ads-stat__val">{{ activeBannersCount ?? 0 }}</span>
                        <span class="vidu-ads-stat__lbl">Banners activos</span>
                    </div>
                    <div class="vidu-ads-stat">
                        <span class="vidu-ads-stat__val">{{ stats?.total_videos ?? 0 }}</span>
                        <span class="vidu-ads-stat__lbl">Videos Vidu</span>
                    </div>
                </div>
            </div>

            <!-- Pestañas de navegación -->
            <div class="vidu-ads-tabs">
                <button
                    v-for="tab in [
                        { id: 'banners', icon: 'fa-solid fa-image', label: 'Banners Laterales' },
                        { id: 'creatives', icon: 'fa-solid fa-film', label: 'Pausas de Video' },
                        { id: 'config', icon: 'fa-solid fa-sliders', label: 'Configuración y Alcance' },
                    ]"
                    :key="tab.id"
                    type="button"
                    class="vidu-ads-tab"
                    :class="{ 'vidu-ads-tab--active': activeTab === tab.id }"
                    @click="activeTab = tab.id"
                >
                    <FaIcon :icon="tab.icon" />
                    {{ tab.label }}
                </button>
            </div>

            <!-- ══════════════ TAB: BANNERS LATERALES ══════════════ -->
            <div v-show="activeTab === 'banners'" class="space-y-4">

                <!-- Formulario de subida de banner -->
                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header flex items-center gap-2">
                        <FaIcon icon="fa-solid fa-cloud-arrow-up" />
                        Subir nuevo banner lateral
                    </div>
                    <form class="p-4" @submit.prevent="uploadBanner">
                        <p class="mb-4 text-xs text-fb-muted">
                            Sube imágenes en formato vertical (JPG, PNG, GIF animado o WebP).
                            Se muestran en el lateral derecho del feed Vidu con rotación aleatoria entre los activos.
                        </p>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="gofio-field-label">Nombre interno</label>
                                <input v-model="bannerForm.name" type="text" class="gofio-input w-full text-sm" placeholder="Ej. Promo verano" required />
                                <p v-if="bannerForm.errors.name" class="mt-1 text-xs text-red-600">{{ bannerForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="gofio-field-label">Imagen vertical</label>
                                <input
                                    ref="bannerInputRef"
                                    type="file"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="gofio-input w-full text-sm"
                                    @change="onBannerSelected"
                                />
                                <p v-if="bannerForm.errors.image" class="mt-1 text-xs text-red-600">{{ bannerForm.errors.image }}</p>
                            </div>
                            <div>
                                <label class="gofio-field-label">Enlace al hacer clic (opcional)</label>
                                <input v-model="bannerForm.link_url" type="url" class="gofio-input w-full text-sm" placeholder="https://..." />
                                <p v-if="bannerForm.errors.link_url" class="mt-1 text-xs text-red-600">{{ bannerForm.errors.link_url }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="gofio-btn-primary text-sm" :disabled="bannerForm.processing || !bannerForm.image">
                                <FaIcon :icon="bannerForm.processing ? 'fa-solid fa-spinner' : 'fa-solid fa-cloud-arrow-up'" :class="{ 'animate-spin': bannerForm.processing }" />
                                {{ bannerForm.processing ? 'Subiendo…' : 'Subir banner' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Grid de banners existentes -->
                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header flex items-center justify-between gap-2">
                        <span>Banners actuales</span>
                        <span class="text-xs text-fb-muted">{{ activeBannersCount }} activos · rotación aleatoria</span>
                    </div>

                    <div v-if="!banners?.length" class="p-6 text-center text-sm text-fb-muted">
                        No hay banners. Sube uno para empezar.
                    </div>

                    <!-- Grid de cards de banner -->
                    <div v-else class="vidu-ads-banner-grid p-4">
                        <div
                            v-for="banner in banners"
                            :key="banner.id"
                            class="vidu-ads-banner-card"
                            :class="{ 'vidu-ads-banner-card--inactive': !banner.is_active }"
                        >
                            <!-- Vista previa de imagen -->
                            <div class="vidu-ads-banner-card__img-wrap">
                                <img :src="banner.image_url" :alt="banner.name" class="vidu-ads-banner-card__img" />
                                <span v-if="!banner.is_active" class="vidu-ads-banner-card__inactive-badge">Inactivo</span>
                            </div>
                            <!-- Nombre + enlace -->
                            <div class="p-2">
                                <p class="truncate text-xs font-semibold" :title="banner.name">{{ banner.name }}</p>
                                <a
                                    v-if="banner.link_url"
                                    :href="banner.link_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="block truncate text-[0.65rem] text-fb-link hover:underline"
                                    :title="banner.link_url"
                                >{{ banner.link_url }}</a>
                            </div>
                            <!-- Acciones -->
                            <div class="flex items-center justify-between gap-1 border-t border-fb-border p-2">
                                <button
                                    type="button"
                                    class="vidu-ads-banner-card__btn"
                                    :class="banner.is_active ? 'text-amber-600' : 'text-emerald-600'"
                                    @click="toggleBanner(banner)"
                                >
                                    <FaIcon :icon="banner.is_active ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" />
                                    {{ banner.is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                                <button type="button" class="vidu-ads-banner-card__btn text-red-500" @click="deleteBanner(banner)">
                                    <FaIcon icon="fa-solid fa-trash" />
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════ TAB: PAUSAS DE VIDEO ══════════════ -->
            <div v-show="activeTab === 'creatives'" class="space-y-4">

                <!-- Formulario de subida de video -->
                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header flex items-center gap-2">
                        <FaIcon icon="fa-solid fa-cloud-arrow-up" />
                        Subir nuevo video publicitario
                    </div>
                    <form class="p-4" @submit.prevent="uploadCreative">
                        <p class="mb-4 text-xs text-fb-muted">
                            Sube videos MP4, WebM o MOV. Todos los videos <strong>activos</strong> rotan aleatoriamente.
                            El video queda inactivo al subirse; actívalo cuando esté listo.
                        </p>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="gofio-field-label">Nombre interno</label>
                                <input v-model="creativeForm.name" type="text" class="gofio-input w-full text-sm" placeholder="Ej. Promo Creator Plus" required />
                                <p v-if="creativeForm.errors.name" class="mt-1 text-xs text-red-600">{{ creativeForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="gofio-field-label">Archivo de video</label>
                                <input
                                    ref="videoInputRef"
                                    type="file"
                                    accept="video/mp4,video/webm,video/quicktime"
                                    class="gofio-input w-full text-sm"
                                    @change="onCreativeSelected"
                                />
                                <p v-if="creativeForm.errors.video" class="mt-1 text-xs text-red-600">{{ creativeForm.errors.video }}</p>
                            </div>
                            <div>
                                <label class="gofio-field-label">Duración del anuncio (segundos)</label>
                                <input v-model.number="creativeForm.duration_seconds" type="number" min="1" max="120" class="gofio-input w-full text-sm" required />
                                <p class="mt-1 text-xs text-fb-muted">Indica exactamente cuántos segundos dura el anuncio.</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="gofio-btn-primary text-sm" :disabled="creativeForm.processing || !creativeForm.video">
                                <FaIcon :icon="creativeForm.processing ? 'fa-solid fa-spinner' : 'fa-solid fa-cloud-arrow-up'" :class="{ 'animate-spin': creativeForm.processing }" />
                                {{ creativeForm.processing ? 'Subiendo…' : 'Subir video' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de creativos existentes -->
                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header flex items-center justify-between gap-2">
                        <span>Videos publicitarios</span>
                        <span class="text-xs text-fb-muted">{{ activeCreativesCount }} activos · rotan aleatoriamente</span>
                    </div>

                    <div v-if="!creatives?.length" class="p-6 text-center text-sm text-fb-muted">
                        No hay videos publicitarios. Sube uno para habilitar las pausas.
                    </div>

                    <ul v-else class="divide-y divide-fb-border">
                        <li
                            v-for="creative in creatives"
                            :key="creative.id"
                            class="vidu-ads-creative-row"
                            :class="{ 'vidu-ads-creative-row--inactive': !creative.is_active }"
                        >
                            <!-- Icono de estado -->
                            <div class="vidu-ads-creative-row__icon">
                                <FaIcon
                                    :icon="creative.is_active ? 'fa-solid fa-circle-play' : 'fa-solid fa-circle-pause'"
                                    :class="creative.is_active ? 'text-emerald-500' : 'text-fb-muted'"
                                />
                            </div>
                            <!-- Info -->
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-sm">{{ creative.name }}</p>
                                <p class="text-xs text-fb-muted">
                                    {{ fmt(creative.duration_seconds) }} min
                                    <span
                                        class="ml-2 rounded px-1.5 py-0.5 text-[0.65rem] font-semibold"
                                        :class="creative.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                    >
                                        {{ creative.is_active ? 'Activo en rotación' : 'Inactivo' }}
                                    </span>
                                </p>
                            </div>
                            <!-- Acciones -->
                            <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
                                <a :href="creative.video_url" target="_blank" rel="noopener" class="gofio-btn-secondary px-2 py-1 text-xs">
                                    <FaIcon icon="fa-solid fa-play" />
                                    Ver
                                </a>
                                <button
                                    type="button"
                                    class="gofio-btn-secondary px-2 py-1 text-xs"
                                    :class="creative.is_active ? 'text-amber-600' : 'text-emerald-600'"
                                    @click="toggleCreative(creative)"
                                >
                                    <FaIcon :icon="creative.is_active ? 'fa-solid fa-pause' : 'fa-solid fa-play'" />
                                    {{ creative.is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                                <button type="button" class="text-xs text-red-500 hover:underline" @click="deleteCreative(creative)">
                                    <FaIcon icon="fa-solid fa-trash" />
                                    Eliminar
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ══════════════ TAB: CONFIGURACIÓN Y ALCANCE ══════════════ -->
            <div v-show="activeTab === 'config'" class="space-y-4">

                <!-- Configuración global -->
                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header">Configuración global</div>
                    <form class="space-y-5 p-4" @submit.prevent="saveSettings">

                        <!-- Interruptores principales -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="vidu-ads-toggle">
                                <input v-model="settingsForm.enabled" type="checkbox" class="rounded border-fb-border" />
                                <span>
                                    <strong>Activar pausas publicitarias</strong>
                                    <em>Muestra pausas con video en los reels elegibles</em>
                                </span>
                            </label>
                            <label class="vidu-ads-toggle">
                                <input v-model="settingsForm.sidebar_banners_enabled" type="checkbox" class="rounded border-fb-border" />
                                <span>
                                    <strong>Activar banners laterales</strong>
                                    <em>Muestra banners en el lateral derecho del feed</em>
                                </span>
                            </label>
                        </div>

                        <hr class="border-fb-border" />

                        <!-- Alcance: qué videos reciben anuncios -->
                        <div>
                            <h3 class="vidu-ads-section-title">¿A qué videos se aplican las pausas?</h3>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                <label
                                    v-for="opt in [
                                        { value: 'all', label: 'Todos los videos elegibles', desc: 'Cualquier video con duración suficiente.' },
                                        { value: 'creator_vip', label: 'Solo videos de creadores VIP', desc: 'Videos de usuarios con Creator Plus activo.' },
                                        { value: 'only_selected', label: 'Solo los marcados manualmente', desc: 'Únicamente los videos con fuerza activada abajo.' },
                                        { value: 'none', label: 'Ninguno (solo forzados)', desc: 'El sistema está desactivado para todos excepto forzados.' },
                                    ]"
                                    :key="opt.value"
                                    class="vidu-ads-radio-card"
                                    :class="{ 'vidu-ads-radio-card--active': settingsForm.apply_mode === opt.value }"
                                >
                                    <input v-model="settingsForm.apply_mode" type="radio" :value="opt.value" class="mt-0.5 flex-shrink-0" />
                                    <span>
                                        <strong>{{ opt.label }}</strong>
                                        <em>{{ opt.desc }}</em>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <hr class="border-fb-border" />

                        <!-- Audiencia: quién ve los anuncios -->
                        <div>
                            <h3 class="vidu-ads-section-title">¿Quién ve los anuncios?</h3>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                <label
                                    v-for="opt in [
                                        { value: 'everyone', label: 'Todos los espectadores', desc: 'Cualquier usuario ve las pausas publicitarias.' },
                                        { value: 'non_vip', label: 'Solo espectadores no VIP', desc: 'Los usuarios Creator Plus ven los reels sin anuncios.' },
                                    ]"
                                    :key="opt.value"
                                    class="vidu-ads-radio-card"
                                    :class="{ 'vidu-ads-radio-card--active': settingsForm.viewer_mode === opt.value }"
                                >
                                    <input v-model="settingsForm.viewer_mode" type="radio" :value="opt.value" class="mt-0.5 flex-shrink-0" />
                                    <span>
                                        <strong>{{ opt.label }}</strong>
                                        <em>{{ opt.desc }}</em>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <hr class="border-fb-border" />

                        <!-- Parámetros de tiempo -->
                        <div>
                            <h3 class="vidu-ads-section-title">Parámetros de tiempo</h3>
                            <div class="mt-2 grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="gofio-field-label">Duración mínima del video (s)</label>
                                    <input v-model.number="settingsForm.min_video_seconds" type="number" min="60" max="600" class="gofio-input w-full text-sm" />
                                    <p class="mt-1 text-xs text-fb-muted">Videos más cortos no reciben anuncios.</p>
                                </div>
                                <div>
                                    <label class="gofio-field-label">Pausa desde (segundos)</label>
                                    <input v-model.number="settingsForm.trigger_min_seconds" type="number" min="10" max="300" class="gofio-input w-full text-sm" />
                                </div>
                                <div>
                                    <label class="gofio-field-label">Pausa hasta (% del video)</label>
                                    <input v-model.number="settingsForm.trigger_max_percent" type="number" min="30" max="90" class="gofio-input w-full text-sm" />
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-fb-muted">
                                La pausa ocurre en un segundo aleatorio y estable por video dentro de ese rango.
                            </p>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="gofio-btn-primary" :disabled="settingsForm.processing">
                                <FaIcon :icon="settingsForm.processing ? 'fa-solid fa-spinner' : 'fa-solid fa-floppy-disk'" :class="{ 'animate-spin': settingsForm.processing }" />
                                Guardar configuración
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Override por video individual -->
                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header flex flex-wrap items-center justify-between gap-2">
                        <span>Alcance por video</span>
                        <span class="text-xs text-fb-muted">
                            {{ stats?.forced_on ?? 0 }} forzados con · {{ stats?.forced_off ?? 0 }} sin · {{ stats?.total_videos ?? 0 }} totales
                        </span>
                    </div>

                    <!-- Filtros -->
                    <div class="flex flex-wrap items-center gap-2 border-b border-fb-border bg-[#F5F6F7] p-3">
                        <input
                            v-model="searchQ"
                            type="search"
                            placeholder="Buscar por título o autor…"
                            class="gofio-input min-w-[12rem] flex-1 text-xs"
                            @keyup.enter="reloadVideos"
                        />
                        <select v-model="adsFilter" class="gofio-input w-auto text-xs" @change="reloadVideos">
                            <option value="">Todas las reglas</option>
                            <option value="with">Forzados con publicidad</option>
                            <option value="without">Forzados sin publicidad</option>
                            <option value="default">Regla global</option>
                        </select>
                        <button type="button" class="gofio-btn-primary text-xs" @click="reloadVideos">Filtrar</button>
                    </div>

                    <!-- Acciones masivas -->
                    <div class="flex flex-wrap gap-2 border-b border-fb-border p-3">
                        <button type="button" class="gofio-btn-secondary px-2 py-1 text-xs" @click="bulkAction('enable_all')">
                            <FaIcon icon="fa-solid fa-circle-check" class="text-emerald-600" />
                            Forzar en todos
                        </button>
                        <button type="button" class="gofio-btn-secondary px-2 py-1 text-xs" @click="bulkAction('disable_all')">
                            <FaIcon icon="fa-solid fa-circle-xmark" class="text-red-500" />
                            Quitar en todos
                        </button>
                        <button type="button" class="gofio-btn-secondary px-2 py-1 text-xs" @click="bulkAction('reset_all')">
                            <FaIcon icon="fa-solid fa-rotate-left" />
                            Restablecer todos
                        </button>
                    </div>

                    <!-- Tabla de videos -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-[#F5F6F7] text-left text-xs text-fb-muted">
                                <tr>
                                    <th class="px-4 py-2 font-medium">Video</th>
                                    <th class="px-4 py-2 font-medium">Autor</th>
                                    <th class="px-4 py-2 font-medium">Duración</th>
                                    <th class="px-4 py-2 font-medium">Regla</th>
                                    <th class="px-4 py-2 font-medium">Override</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-fb-border">
                                <tr v-for="video in videos.data" :key="video.id" class="hover:bg-[#FAFBFC]">
                                    <td class="px-4 py-2.5">
                                        <p class="font-medium">{{ video.title || `Video #${video.id}` }}</p>
                                        <p class="text-xs text-fb-muted">ID {{ video.id }}</p>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs">@{{ video.user?.username }}</td>
                                    <td class="px-4 py-2.5 text-xs">{{ fmt(video.duration_seconds) }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="rounded px-2 py-0.5 text-xs font-medium" :class="videoAdsClass(video)">
                                            {{ videoAdsLabel(video) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex flex-wrap gap-1">
                                            <button type="button" class="rounded bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700 hover:bg-emerald-100" @click="setVideoMode(video, 'force_on')">
                                                Con pub.
                                            </button>
                                            <button type="button" class="rounded bg-red-50 px-2 py-0.5 text-xs text-red-600 hover:bg-red-100" @click="setVideoMode(video, 'force_off')">
                                                Sin pub.
                                            </button>
                                            <button type="button" class="rounded bg-gray-100 px-2 py-0.5 text-xs text-fb-muted hover:bg-gray-200" @click="setVideoMode(video, 'default')">
                                                Global
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <AdminPagination :paginator="videos" item-label="videos" class="border-t border-fb-border p-3" />
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>
/* ── Cabecera de estado ──────────────────────────────────────────────────── */
.vidu-ads-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border: 1px solid var(--color-border, #dadde1);
    border-radius: 0.5rem;
}

.vidu-ads-header__title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 700;
}

.vidu-ads-header__stats {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.vidu-ads-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.1rem;
}

.vidu-ads-stat__val {
    font-size: 1.1rem;
    font-weight: 800;
    line-height: 1;
}

.vidu-ads-stat__lbl {
    font-size: 0.65rem;
    color: var(--color-muted, #90949c);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

/* ── Pestañas ───────────────────────────────────────────────────────────── */
.vidu-ads-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    border-bottom: 2px solid var(--color-border, #dadde1);
    background: white;
    padding: 0 0.5rem;
    border-radius: 0.5rem 0.5rem 0 0;
    overflow: hidden;
}

.vidu-ads-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.65rem 1rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-muted, #90949c);
    border-bottom: 2.5px solid transparent;
    margin-bottom: -2px;
    transition: color 0.15s ease, border-color 0.15s ease;
}

.vidu-ads-tab:hover {
    color: var(--color-text, #1c1e21);
}

.vidu-ads-tab--active {
    color: var(--color-brand);
    border-bottom-color: var(--color-brand);
}

/* ── Grid de banners ────────────────────────────────────────────────────── */
.vidu-ads-banner-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(9rem, 1fr));
    gap: 0.75rem;
}

.vidu-ads-banner-card {
    border: 1px solid var(--color-border, #dadde1);
    border-radius: 0.5rem;
    overflow: hidden;
    background: white;
    transition: box-shadow 0.15s ease;
}

.vidu-ads-banner-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.vidu-ads-banner-card--inactive {
    opacity: 0.55;
}

.vidu-ads-banner-card__img-wrap {
    position: relative;
    height: 9rem;
    background: #f0f2f5;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.vidu-ads-banner-card__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.vidu-ads-banner-card__inactive-badge {
    position: absolute;
    top: 0.35rem;
    right: 0.35rem;
    background: rgba(0, 0, 0, 0.55);
    color: white;
    font-size: 0.6rem;
    font-weight: 700;
    padding: 0.1rem 0.4rem;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.vidu-ads-banner-card__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.7rem;
    font-weight: 600;
}

/* ── Lista de creativos ─────────────────────────────────────────────────── */
.vidu-ads-creative-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    transition: background 0.1s ease;
}

.vidu-ads-creative-row:hover {
    background: #fafbfc;
}

.vidu-ads-creative-row--inactive {
    opacity: 0.65;
}

.vidu-ads-creative-row__icon {
    font-size: 1.25rem;
    flex-shrink: 0;
    width: 1.5rem;
    text-align: center;
}

/* ── Radio cards de configuración ───────────────────────────────────────── */
.vidu-ads-radio-card {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.75rem;
    border: 1.5px solid var(--color-border, #dadde1);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;
}

.vidu-ads-radio-card:hover {
    background: #f5f6f7;
}

.vidu-ads-radio-card--active {
    border-color: var(--color-brand);
    background: rgba(20, 184, 166, 0.04);
}

.vidu-ads-radio-card strong {
    display: block;
    font-size: 0.8125rem;
    font-weight: 700;
}

.vidu-ads-radio-card em {
    display: block;
    font-size: 0.7rem;
    font-style: normal;
    color: var(--color-muted, #90949c);
    margin-top: 0.1rem;
}

/* ── Toggles de configuración ───────────────────────────────────────────── */
.vidu-ads-toggle {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.75rem;
    border: 1.5px solid var(--color-border, #dadde1);
    border-radius: 0.5rem;
    cursor: pointer;
}

.vidu-ads-toggle strong {
    display: block;
    font-size: 0.8125rem;
    font-weight: 700;
}

.vidu-ads-toggle em {
    display: block;
    font-size: 0.7rem;
    font-style: normal;
    color: var(--color-muted, #90949c);
}

/* ── Subtítulos de sección ──────────────────────────────────────────────── */
.vidu-ads-section-title {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-brand-hover);
}

.text-brand {
    color: var(--color-brand);
}
</style>
