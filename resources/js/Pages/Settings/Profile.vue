<script setup>
/**
 * Página de edición del perfil de usuario: datos personales, redes sociales, avatar,
 * portada con reposicionamiento por arrastre y preferencias.
 */

import { Link, useForm } from '@inertiajs/vue3';
import { defineAsyncComponent, nextTick, onBeforeUnmount, ref } from 'vue';
import GofioLayout from '@/Layouts/GofioLayout.vue';
import CountrySelect from '@/Components/Profile/CountrySelect.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import { useGiphy } from '@/composables/useGiphy';

const GiphyPickerPopover = defineAsyncComponent(() => import('@/Components/Posts/GiphyPickerPopover.vue'));

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
});

const { configured: giphyConfigured, packIcon: giphyPackIcon } = useGiphy();

const avatarInputRef = ref(null);
const bannerInputRef = ref(null);
const giphyButtonRef = ref(null);
const giphyPanelRef = ref(null);
const showGiphy = ref(false);
const giphyPanelStyle = ref({});
const uploadingAvatar = ref(false);
const uploadingBanner = ref(false);

/** Controla si el modo de reposicionamiento del banner está activo. */
const repositionMode = ref(false);

/** Referencia al contenedor del banner para calcular deltas de arrastre. */
const bannerRef = ref(null);

/** Estado del arrastre: activo, posición inicial del puntero y offset inicial. */
const drag = ref({ active: false, startX: 0, startY: 0, startOffX: 50, startOffY: 50 });

/** Muestra la leyenda de dimensiones de portada al pasar el cursor sobre el banner. */
const hoverBanner = ref(false);
/** Muestra la leyenda de dimensiones del avatar al pasar el cursor sobre el avatar. */
const hoverAvatar = ref(false);

const form = useForm({
    nick: props.profile.nick ?? '',
    email: props.profile.email ?? '',
    current_password: '',
    password: '',
    password_confirmation: '',
    country: props.profile.country ?? '',
    country_code: props.profile.country_code ?? '',
    age: props.profile.age ?? '',
    bio: props.profile.bio ?? '',
    bio_gif_url: props.profile.bio_gif_url ?? '',
    whatsapp: props.profile.whatsapp ?? '',
    instagram: props.profile.instagram ?? '',
    facebook: props.profile.facebook ?? '',
    social_x: props.profile.social_x ?? '',
    avatar_url: props.profile.avatar_url ?? '',
    banner_url: props.profile.banner_url ?? '',
    banner_offset_x: props.profile.banner_offset_x ?? 50,
    banner_offset_y: props.profile.banner_offset_y ?? 50,
});

/** Envía el formulario al servidor y gestiona errores de validación. */
const submit = () => {
    repositionMode.value = false;
    form.put('/configuracion/perfil');
};


/** Posiciona el selector de GIPHY junto al botón de biografía. */
const updateGiphyPanelPosition = () => {
    const button = giphyButtonRef.value;
    if (! button) {
        return;
    }

    const rect = button.getBoundingClientRect();
    const width = 320;
    const height = 380;
    const margin = 8;

    let top = rect.bottom + 8;
    let left = rect.left;

    if (left + width > window.innerWidth - margin) {
        left = window.innerWidth - width - margin;
    }

    giphyPanelStyle.value = {
        position: 'fixed',
        top: `${Math.max(64, top)}px`,
        left: `${Math.max(margin, left)}px`,
        width: `${width}px`,
        maxHeight: `${height}px`,
        zIndex: 9999,
    };
};


/** Muestra u oculta el buscador de GIFs para la biografía. */
const toggleGiphy = () => {
    showGiphy.value = ! showGiphy.value;
    if (showGiphy.value) {
        nextTick(updateGiphyPanelPosition);
    }
};


/** Guarda el GIF elegido en el campo bio_gif_url del formulario. */
const onGiphySelect = (gif) => {
    form.bio_gif_url = gif.url;
    showGiphy.value = false;
};


/** Quita el GIF de la biografía. */
const removeBioGif = () => {
    form.bio_gif_url = '';
};


/** Cierra el popover de GIPHY al hacer clic fuera. */
const onDocumentClick = (event) => {
    if (! showGiphy.value) {
        return;
    }

    const inButton = giphyButtonRef.value?.contains(event.target);
    const inPanel = giphyPanelRef.value?.contains(event.target);

    if (! inButton && ! inPanel) {
        showGiphy.value = false;
    }
};

document.addEventListener('mousedown', onDocumentClick);
window.addEventListener('resize', updateGiphyPanelPosition);

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocumentClick);
    window.removeEventListener('resize', updateGiphyPanelPosition);
});

/** Sube avatar o banner al almacenamiento y actualiza la URL en el formulario. */
const uploadImage = async (file, endpoint, field, loadingRef) => {
    if (!file) return;
    loadingRef.value = true;
    try {
        const formData = new FormData();
        formData.append('image', file);
        const { data } = await window.axios.post(endpoint, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        form[field] = data.file.url;
    } catch (e) {
        alert(e.response?.data?.errors?.image?.[0] || 'No se pudo subir la imagen.');
    } finally {
        loadingRef.value = false;
    }
};

/** Sube el avatar elegido y actualiza la URL en el formulario. */
const onAvatarSelected = (event) => {
    uploadImage(event.target.files?.[0], '/api/uploads/profile-avatar', 'avatar_url', uploadingAvatar);
    event.target.value = '';
};

/** Sube el banner elegido; al terminar activa el modo de reposicionamiento automáticamente. */
const onBannerSelected = async (event) => {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;
    await uploadImage(file, '/api/uploads/profile-banner', 'banner_url', uploadingBanner);
    // Centra el offset al subir una nueva imagen.
    form.banner_offset_x = 50;
    form.banner_offset_y = 50;
    repositionMode.value = true;
};

/** Inicia el arrastre capturando la posición inicial del puntero. */
const onDragStart = (clientX, clientY) => {
    if (!repositionMode.value) return;
    drag.value = {
        active: true,
        startX: clientX,
        startY: clientY,
        startOffX: form.banner_offset_x,
        startOffY: form.banner_offset_y,
    };
};

/** Actualiza los offsets durante el arrastre, mapeando el delta al rango 0–100. */
const onDragMove = (clientX, clientY) => {
    if (!drag.value.active || !bannerRef.value) return;
    const rect = bannerRef.value.getBoundingClientRect();
    // Inverso: arrastrar a la derecha mueve la imagen a la derecha (offset disminuye).
    const deltaXPct = ((clientX - drag.value.startX) / rect.width) * 100;
    const deltaYPct = ((clientY - drag.value.startY) / rect.height) * 100;
    form.banner_offset_x = Math.round(Math.min(100, Math.max(0, drag.value.startOffX - deltaXPct)));
    form.banner_offset_y = Math.round(Math.min(100, Math.max(0, drag.value.startOffY - deltaYPct)));
};

const onDragEnd = () => { drag.value.active = false; };

// Manejadores de mouse
const onMouseDown = (e) => { e.preventDefault(); onDragStart(e.clientX, e.clientY); };
const onMouseMove = (e) => onDragMove(e.clientX, e.clientY);
const onMouseUp = () => onDragEnd();

// Manejadores de touch (dispositivos móviles)
const onTouchStart = (e) => { const t = e.touches[0]; onDragStart(t.clientX, t.clientY); };
const onTouchMove = (e) => { e.preventDefault(); const t = e.touches[0]; onDragMove(t.clientX, t.clientY); };
const onTouchEnd = () => onDragEnd();
</script>

<template>
    <!-- Formulario de edición de datos del perfil -->

    <GofioLayout>
        <template #feed>
            <div class="gofio-box overflow-hidden">
                <div class="gofio-box-header-accent flex items-center justify-between gap-2">
                    <span>Editar perfil</span>
                    <Link
                        :href="`/perfil/${profile.username}`"
                        class="text-xs font-normal opacity-90 hover:underline"
                    >
                        Ver mi perfil público
                    </Link>
                </div>

                <!-- Sección de portada y avatar con reposicionamiento interactivo -->
                <div class="profile-edit-cover">

                    <!-- Banner / portada: leyenda aparece al hacer hover sobre esta área -->
                    <div
                        ref="bannerRef"
                        class="profile-edit-cover__banner"
                        :class="{ 'profile-edit-cover__banner--reposition': repositionMode }"
                        :style="form.banner_url
                            ? { backgroundImage: `url(${form.banner_url})`, backgroundPosition: `${form.banner_offset_x}% ${form.banner_offset_y}%` }
                            : {}"
                        @mouseenter="hoverBanner = true"
                        @mouseleave="hoverBanner = false; onMouseUp()"
                        @mousedown="repositionMode ? onMouseDown($event) : null"
                        @mousemove="onMouseMove"
                        @mouseup="onMouseUp"
                        @touchstart.passive="repositionMode ? onTouchStart($event) : null"
                        @touchmove.prevent="repositionMode ? onTouchMove($event) : null"
                        @touchend="onTouchEnd"
                    >
                        <!-- Leyenda de dimensiones de portada: visible solo al hacer hover -->
                        <Transition name="media-hint">
                            <div
                                v-if="hoverBanner || uploadingBanner"
                                class="profile-edit-banner-hint"
                            >
                                <FaIcon icon="fa-solid fa-circle-info" />
                                Portada recomendada: <strong>1500 × 500 px</strong> · JPG, PNG o WebP · máx. 5 MB
                            </div>
                        </Transition>

                        <!-- Overlay de instrucción durante el reposicionamiento -->
                        <div v-if="repositionMode" class="profile-edit-cover__reposition-hint">
                            <FaIcon icon="fa-solid fa-arrows-up-down-left-right" />
                            <span>Arrastra para reposicionar la imagen</span>
                        </div>

                        <!-- Controles del banner: cambiar foto y (si hay imagen) reposicionar -->
                        <div class="profile-edit-cover__banner-controls">
                            <template v-if="repositionMode">
                                <button
                                    type="button"
                                    class="profile-edit-cover__btn profile-edit-cover__btn--confirm"
                                    @click.stop="repositionMode = false"
                                >
                                    <FaIcon icon="fa-solid fa-check" />
                                    Listo
                                </button>
                            </template>
                            <template v-else>
                                <button
                                    v-if="form.banner_url"
                                    type="button"
                                    class="profile-edit-cover__btn profile-edit-cover__btn--reposition"
                                    @click.stop="repositionMode = true"
                                >
                                    <FaIcon icon="fa-solid fa-arrows-up-down-left-right" />
                                    Reposicionar
                                </button>
                                <button
                                    type="button"
                                    class="profile-edit-cover__btn"
                                    :disabled="uploadingBanner"
                                    @click.stop="bannerInputRef?.click()"
                                >
                                    <FaIcon :icon="uploadingBanner ? 'fa-solid fa-spinner' : 'fa-solid fa-camera'" :class="{ 'animate-spin': uploadingBanner }" />
                                    {{ uploadingBanner ? 'Subiendo...' : 'Cambiar portada' }}
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Avatar: leyenda aparece al hacer hover sobre esta área -->
                    <div
                        class="profile-edit-cover__avatar-wrap"
                        @mouseenter="hoverAvatar = true"
                        @mouseleave="hoverAvatar = false"
                    >
                        <div class="profile-edit-cover__avatar">
                            <img v-if="form.avatar_url" :src="form.avatar_url" alt="" class="h-full w-full object-cover" />
                            <span v-else>{{ profile.username.charAt(0).toUpperCase() }}</span>
                        </div>
                        <button
                            type="button"
                            class="profile-edit-cover__avatar-btn"
                            :disabled="uploadingAvatar"
                            @click="avatarInputRef?.click()"
                        >
                            <FaIcon :icon="uploadingAvatar ? 'fa-solid fa-spinner' : 'fa-solid fa-camera'" :class="{ 'animate-spin': uploadingAvatar }" />
                        </button>

                        <!-- Leyenda del avatar: tooltip a la derecha, visible solo al hacer hover -->
                        <Transition name="media-hint">
                            <div
                                v-if="hoverAvatar || uploadingAvatar"
                                class="profile-edit-avatar-hint"
                            >
                                <FaIcon icon="fa-solid fa-circle-info" />
                                Foto de perfil: <strong>400 × 400 px</strong> · cuadrada · máx. 2 MB
                            </div>
                        </Transition>
                    </div>
                </div>

    <!-- Formulario principal -->
                <form class="space-y-6 p-4" @submit.prevent="submit">
                    <section class="profile-edit-section">
                        <h3 class="profile-edit-section__title">Cuenta</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="profile-edit-label">Nombre de usuario</label>
                                <p class="text-sm text-fb-muted">{{ profile.username }} (no se puede cambiar)</p>
                            </div>

                            <div>
                                <label class="profile-edit-label">@nick <span class="font-normal text-fb-muted">(opcional)</span></label>
                                <div class="gofio-input flex max-w-sm items-center gap-1">
                                    <span class="text-fb-muted">@</span>
                                    <input
                                        v-model="form.nick"
                                        type="text"
                                        class="w-full border-0 bg-transparent p-0 outline-none focus:ring-0"
                                        placeholder="tu_nick"
                                        autocomplete="off"
                                    />
                                </div>
                                <p v-if="form.errors.nick" class="mt-1 text-xs text-red-600">{{ form.errors.nick }}</p>
                            </div>

                            <div>
                                <label class="profile-edit-label">Correo electrónico</label>
                                <input v-model="form.email" type="email" class="gofio-input max-w-md" autocomplete="email" />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="profile-edit-section">
                        <h3 class="profile-edit-section__title">Seguridad</h3>
                        <div class="grid gap-3 sm:max-w-md">
                            <div>
                                <label class="profile-edit-label">Contraseña actual</label>
                                <input v-model="form.current_password" type="password" class="gofio-input" autocomplete="current-password" />
                                <p v-if="form.errors.current_password" class="mt-1 text-xs text-red-600">{{ form.errors.current_password }}</p>
                            </div>
                            <div>
                                <label class="profile-edit-label">Nueva contraseña</label>
                                <input v-model="form.password" type="password" class="gofio-input" autocomplete="new-password" />
                                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                            </div>
                            <div>
                                <label class="profile-edit-label">Confirmar nueva contraseña</label>
                                <input v-model="form.password_confirmation" type="password" class="gofio-input" autocomplete="new-password" />
                            </div>
                        </div>
                    </section>

                    <section class="profile-edit-section">
                        <h3 class="profile-edit-section__title">Información personal</h3>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="profile-edit-label">País</label>
                                <CountrySelect
                                    v-model="form.country"
                                    v-model:country-code="form.country_code"
                                    :error="form.errors.country || form.errors.country_code"
                                />
                            </div>

                            <div>
                                <label class="profile-edit-label">Edad</label>
                                <input v-model="form.age" type="number" min="13" max="120" class="gofio-input" placeholder="Ej. 25" />
                                <p v-if="form.errors.age" class="mt-1 text-xs text-red-600">{{ form.errors.age }}</p>
                            </div>

                            <div>
                                <label class="profile-edit-label">WhatsApp</label>
                                <div class="gofio-input flex items-center gap-2">
                                    <FaIcon icon="fa-brands fa-whatsapp" class="text-emerald-600" />
                                    <input
                                        v-model="form.whatsapp"
                                        type="text"
                                        class="w-full border-0 bg-transparent p-0 outline-none focus:ring-0"
                                        placeholder="+54 9 11 1234 5678"
                                    />
                                </div>
                                <p v-if="form.errors.whatsapp" class="mt-1 text-xs text-red-600">{{ form.errors.whatsapp }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="profile-edit-label">Descripción breve</label>
                                <textarea
                                    v-model="form.bio"
                                    class="gofio-input min-h-[90px] resize-none"
                                    maxlength="300"
                                    placeholder="Cuéntanos un poco sobre ti..."
                                />
                                <p class="mt-1 text-xs text-fb-muted">{{ (form.bio || '').length }}/300</p>
                                <p v-if="form.errors.bio" class="mt-1 text-xs text-red-600">{{ form.errors.bio }}</p>
                            </div>

                            <!-- GIF opcional de GIPHY en la biografía -->
                            <div v-if="giphyConfigured" class="sm:col-span-2">
                                <label class="profile-edit-label">GIF de biografía (GIPHY)</label>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        ref="giphyButtonRef"
                                        type="button"
                                        class="gofio-btn-secondary text-xs"
                                        @click="toggleGiphy"
                                    >
                                        <FaIcon :icon="giphyPackIcon" class="mr-1.5" />
                                        Elegir GIF
                                    </button>
                                    <button
                                        v-if="form.bio_gif_url"
                                        type="button"
                                        class="gofio-btn-secondary text-xs text-red-600"
                                        @click="removeBioGif"
                                    >
                                        Quitar GIF
                                    </button>
                                </div>
                                <div v-if="form.bio_gif_url" class="mt-2 inline-block">
                                    <img :src="form.bio_gif_url" alt="GIF de biografía" class="max-h-32 rounded border border-fb-border" />
                                </div>
                                <p v-if="form.errors.bio_gif_url" class="mt-1 text-xs text-red-600">{{ form.errors.bio_gif_url }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="profile-edit-section">
                        <h3 class="profile-edit-section__title">Redes sociales</h3>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="profile-edit-label">
                                    <FaIcon icon="fa-brands fa-instagram" class="text-[#c13584]" />
                                    Instagram
                                </label>
                                <input v-model="form.instagram" type="text" class="gofio-input" placeholder="usuario" />
                                <p v-if="form.errors.instagram" class="mt-1 text-xs text-red-600">{{ form.errors.instagram }}</p>
                            </div>
                            <div>
                                <label class="profile-edit-label">
                                    <FaIcon icon="fa-brands fa-facebook-f" class="text-[#1877f2]" />
                                    Facebook
                                </label>
                                <input v-model="form.facebook" type="text" class="gofio-input" placeholder="usuario" />
                                <p v-if="form.errors.facebook" class="mt-1 text-xs text-red-600">{{ form.errors.facebook }}</p>
                            </div>
                            <div>
                                <label class="profile-edit-label">
                                    <FaIcon icon="fa-brands fa-x-twitter" />
                                    X (Twitter)
                                </label>
                                <input v-model="form.social_x" type="text" class="gofio-input" placeholder="usuario" />
                                <p v-if="form.errors.social_x" class="mt-1 text-xs text-red-600">{{ form.errors.social_x }}</p>
                            </div>
                        </div>
                    </section>

                    <div class="flex flex-wrap gap-2 border-t border-fb-border pt-4">
                        <button type="submit" class="gofio-btn-primary" :disabled="form.processing">
                            Guardar cambios
                        </button>
                        <Link :href="`/perfil/${profile.username}`" class="gofio-btn-secondary">
                            Cancelar
                        </Link>
                    </div>
                </form>

                <input ref="avatarInputRef" type="file" accept="image/png,image/jpeg,image/gif,image/webp,image/bmp" class="hidden" @change="onAvatarSelected" />
                <input ref="bannerInputRef" type="file" accept="image/png,image/jpeg,image/gif,image/webp,image/bmp" class="hidden" @change="onBannerSelected" />

                <Teleport to="body">
                    <div
                        v-if="showGiphy"
                        ref="giphyPanelRef"
                        class="emoji-floating-panel"
                        :style="giphyPanelStyle"
                        @mousedown.prevent
                    >
                        <GiphyPickerPopover @select="onGiphySelect" />
                    </div>
                </Teleport>
            </div>
        </template>
    </GofioLayout>
</template>

<style scoped>
.profile-edit-cover {
    position: relative;
}

/* Banner / portada del perfil */
.profile-edit-cover__banner {
    height: 10rem;
    background: linear-gradient(135deg, var(--color-brand), var(--color-brand-hover));
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    padding: 0.75rem;
    transition: background-position 0.05s linear;
    position: relative;
    overflow: hidden;
}

/* Cursor de arrastre en modo reposicionamiento */
.profile-edit-cover__banner--reposition {
    cursor: grab;
    user-select: none;
}
.profile-edit-cover__banner--reposition:active {
    cursor: grabbing;
}

/* Overlay semitransparente con instrucción de arrastre */
.profile-edit-cover__reposition-hint {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: rgba(0, 0, 0, 0.42);
    color: white;
    font-size: 0.8rem;
    font-weight: 600;
    pointer-events: none;
    letter-spacing: 0.01em;
}

.profile-edit-cover__reposition-hint svg,
.profile-edit-cover__reposition-hint i {
    font-size: 1.4rem;
    opacity: 0.9;
}

/* Fila de botones en la esquina inferior derecha del banner */
.profile-edit-cover__banner-controls {
    display: flex;
    gap: 0.4rem;
    position: relative;
    z-index: 1;
}

.profile-edit-cover__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    background: rgba(0, 0, 0, 0.55);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    backdrop-filter: blur(4px);
    transition: background 0.15s ease;
}

.profile-edit-cover__btn:hover:not(:disabled) {
    background: rgba(0, 0, 0, 0.72);
}

.profile-edit-cover__btn--reposition {
    background: rgba(20, 184, 166, 0.75);
}

.profile-edit-cover__btn--reposition:hover:not(:disabled) {
    background: rgba(20, 184, 166, 0.95);
}

/* Botón "Listo" para confirmar posición */
.profile-edit-cover__btn--confirm {
    background: rgba(34, 197, 94, 0.8);
}

.profile-edit-cover__btn--confirm:hover:not(:disabled) {
    background: rgba(34, 197, 94, 1);
}

/* Leyenda de dimensiones de portada: barra en la parte superior del banner */
.profile-edit-banner-hint {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.75rem;
    background: rgba(0, 0, 0, 0.6);
    color: rgba(255, 255, 255, 0.92);
    font-size: 0.7rem;
    font-weight: 500;
    backdrop-filter: blur(4px);
    z-index: 2;
    pointer-events: none;
}

/* Leyenda del avatar: tooltip a la derecha del avatar flotante */
.profile-edit-avatar-hint {
    position: absolute;
    left: calc(100% + 0.6rem);
    top: 50%;
    transform: translateY(-50%);
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.65rem;
    background: rgba(15, 23, 42, 0.82);
    color: rgba(255, 255, 255, 0.92);
    font-size: 0.68rem;
    font-weight: 500;
    border-radius: 0.4rem;
    backdrop-filter: blur(4px);
    z-index: 10;
    pointer-events: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
}

/* Flecha izquierda del tooltip del avatar */
.profile-edit-avatar-hint::before {
    content: '';
    position: absolute;
    right: 100%;
    top: 50%;
    transform: translateY(-50%);
    border: 5px solid transparent;
    border-right-color: rgba(15, 23, 42, 0.82);
}

/* Avatar flotante sobre el banner */
.profile-edit-cover__avatar-wrap {
    position: absolute;
    left: 1rem;
    bottom: -2.5rem;
}

.profile-edit-cover__avatar {
    display: flex;
    height: 5rem;
    width: 5rem;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 0.5rem;
    border: 4px solid white;
    background: #dadde1;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-muted);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
}

.profile-edit-cover__avatar-btn {
    position: absolute;
    right: -0.25rem;
    bottom: -0.25rem;
    display: flex;
    height: 2rem;
    width: 2rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: var(--color-brand);
    color: white;
    font-size: 0.75rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.2);
    transition: background 0.15s ease;
}

.profile-edit-cover__avatar-btn:hover:not(:disabled) {
    background: var(--color-brand-hover);
}

.profile-edit-section {
    padding-top: 0.25rem;
}

.profile-edit-section__title {
    margin-bottom: 0.75rem;
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-brand-hover);
}

.profile-edit-label {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.35rem;
    font-size: 0.875rem;
    font-weight: 600;
}

form {
    /* Espacio para el avatar flotante que sobresale del banner */
    padding-top: 3.5rem !important;
}

/* Transición suave de aparición/desaparición de las leyendas */
.media-hint-enter-active,
.media-hint-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.media-hint-enter-from,
.media-hint-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
.media-hint-enter-to,
.media-hint-leave-from {
    opacity: 1;
    transform: translateY(0);
}
</style>
