<script setup>
/**
 * Compositor Vidu: formulario de subida de video con validación de duración en cliente,
 * captura automática de thumbnail con Canvas y preview antes de enviar.
 */

import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onBeforeUnmount } from 'vue';
import GofioLayout from '@/Layouts/GofioLayout.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';

const props = defineProps({
    maxDuration: { type: Number, default: 60 },
    maxSizeMb:   { type: Number, default: 100 },
    isCreator:   { type: Boolean, default: false },
});

const page = usePage();

// ---------- Estado ----------
const fileInput = ref(null);
const selectedFile = ref(null);
const previewUrl = ref('');
const videoRef = ref(null);
const thumbnailDataUrl = ref('');
const duration = ref(0);
const title = ref('');
const description = ref('');
const uploading = ref(false);
const progress = ref(0);
const error = ref('');
const success = ref('');

// ---------- Computed ----------
const durationFormatted = computed(() => {
    const s = Math.floor(duration.value);
    const m = Math.floor(s / 60);
    return `${m}:${(s % 60).toString().padStart(2, '0')}`;
});

const maxDurationFormatted = computed(() => {
    const m = Math.floor(props.maxDuration / 60);
    const s = props.maxDuration % 60;
    return m > 0 ? `${m}:${s.toString().padStart(2, '0')}` : `${s}s`;
});

const durationValid = computed(() => duration.value > 0 && duration.value <= props.maxDuration);
const canSubmit = computed(() => selectedFile.value && durationValid.value && !uploading.value);

// ---------- Selección de archivo ----------
const pickFile = () => fileInput.value?.click();

const onFileSelected = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    error.value = '';
    success.value = '';
    thumbnailDataUrl.value = '';
    duration.value = 0;

    // Valida tipo MIME en cliente
    const allowed = ['video/mp4', 'video/webm', 'video/quicktime', 'video/avi', 'video/x-matroska', 'video/3gpp'];
    if (!allowed.includes(file.type) && !file.name.match(/\.(mp4|webm|mov|avi|mkv|m4v|3gp)$/i)) {
        error.value = 'Formato no soportado. Usa MP4, WebM, MOV o MKV.';
        return;
    }

    // Valida tamaño en cliente
    if (file.size > props.maxSizeMb * 1024 * 1024) {
        error.value = `El archivo supera los ${props.maxSizeMb} MB permitidos.`;
        return;
    }

    selectedFile.value = file;

    // Previsualización local
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = URL.createObjectURL(file);
};

// Captura duración + thumbnail del primer fotograma
const onVideoLoaded = () => {
    const vid = videoRef.value;
    if (!vid) return;

    duration.value = vid.duration || 0;

    if (duration.value > props.maxDuration) {
        error.value = `El video dura ${durationFormatted.value}, el máximo es ${maxDurationFormatted.value}.`;
    }

    // Captura thumbnail del primer fotograma vía Canvas
    try {
        vid.currentTime = 0.5; // Ir a 0.5s para evitar frame negro
    } catch { /* ignorar */ }
};

const onSeeked = () => {
    const vid = videoRef.value;
    if (!vid || thumbnailDataUrl.value) return;
    try {
        const canvas = document.createElement('canvas');
        canvas.width = vid.videoWidth;
        canvas.height = vid.videoHeight;
        canvas.getContext('2d')?.drawImage(vid, 0, 0);
        thumbnailDataUrl.value = canvas.toDataURL('image/jpeg', 0.7);
    } catch { /* Canvas tainted: omitir thumbnail */ }
};

onBeforeUnmount(() => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});

// ---------- Subida ----------
const submit = async () => {
    if (!canSubmit.value) return;

    uploading.value = true;
    progress.value = 0;
    error.value = '';

    const formData = new FormData();
    formData.append('video', selectedFile.value);
    formData.append('duration_seconds', String(Math.floor(duration.value)));
    if (title.value.trim()) formData.append('title', title.value.trim());
    if (description.value.trim()) formData.append('description', description.value.trim());
    if (thumbnailDataUrl.value) formData.append('thumbnail_data_url', thumbnailDataUrl.value);

    try {
        const res = await new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/api/vidu/upload');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', page.props.csrf_token);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) progress.value = Math.round((e.loaded / e.total) * 100);
            };

            xhr.onload = () => {
                try { resolve(JSON.parse(xhr.responseText)); } catch { reject(new Error('Respuesta inválida.')); }
            };
            xhr.onerror = () => reject(new Error('Error de red.'));
            xhr.send(formData);
        });

        if (res.success) {
            success.value = '¡Video publicado! Redirigiendo al feed…';
            setTimeout(() => { window.location.href = '/vidu'; }, 1500);
        } else {
            error.value = res.message || 'No se pudo publicar el video.';
        }
    } catch (err) {
        error.value = err.message || 'Error al subir el video.';
    } finally {
        uploading.value = false;
    }
};
</script>

<template>
    <GofioLayout>
        <div class="vidu-crear-page gofio-box overflow-hidden">
            <!-- Cabecera de la página -->
            <div class="gofio-box-header flex items-center gap-2">
                <Link href="/vidu" class="text-white hover:opacity-80">
                    <FaIcon icon="fa-solid fa-chevron-left" />
                </Link>
                <FaIcon icon="fa-solid fa-film" />
                Publicar video en Vidu
            </div>

            <div class="p-4 space-y-5">
                <!-- Límites según el plan -->
                <div class="rounded border border-fb-border bg-[#F5F6F7] p-3 text-sm">
                    <span v-if="isCreator" class="flex items-center gap-2 text-emerald-700 font-medium">
                        <FaIcon icon="fa-solid fa-crown" />
                        Creador verificado · Duración máx. {{ maxDuration / 60 }} min · Tamaño máx. {{ maxSizeMb }} MB
                    </span>
                    <span v-else class="text-fb-muted">
                        Duración máx. {{ maxDuration }}s · Tamaño máx. {{ maxSizeMb }} MB ·
                        <Link href="/ajustes/creator-plus" class="text-fb-link hover:underline">Creator Plus = 3 min</Link>
                    </span>
                </div>

                <!-- Selección de archivo -->
                <input
                    ref="fileInput"
                    type="file"
                    accept="video/mp4,video/webm,video/quicktime,video/avi,video/x-matroska,video/3gpp,.mp4,.webm,.mov,.avi,.mkv,.m4v,.3gp"
                    class="hidden"
                    @change="onFileSelected"
                />

                <div
                    class="vidu-dropzone"
                    :class="{ 'vidu-dropzone--active': selectedFile }"
                    @click="pickFile"
                >
                    <template v-if="!selectedFile">
                        <FaIcon icon="fa-solid fa-cloud-arrow-up" class="vidu-dropzone-icon" />
                        <p class="font-semibold">Haz clic para elegir un video</p>
                        <p class="text-xs text-fb-muted mt-1">MP4, WebM, MOV, AVI, MKV — máx. {{ maxSizeMb }} MB</p>
                    </template>
                    <template v-else>
                        <FaIcon icon="fa-solid fa-circle-check" class="vidu-dropzone-icon text-green-500" />
                        <p class="font-semibold truncate max-w-xs">{{ selectedFile.name }}</p>
                        <p class="text-xs text-fb-muted">Haz clic para cambiar</p>
                    </template>
                </div>

                <!-- Preview del video -->
                <div v-if="previewUrl" class="vidu-preview-wrap">
                    <video
                        ref="videoRef"
                        :src="previewUrl"
                        class="vidu-preview"
                        muted
                        playsinline
                        preload="metadata"
                        controls
                        @loadedmetadata="onVideoLoaded"
                        @seeked="onSeeked"
                    />
                    <div class="vidu-preview-meta">
                        <span v-if="duration > 0" :class="durationValid ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                            <FaIcon :icon="durationValid ? 'fa-solid fa-check' : 'fa-solid fa-triangle-exclamation'" />
                            {{ durationFormatted }} / {{ maxDurationFormatted }}
                        </span>
                    </div>
                </div>

                <!-- Título y descripción -->
                <div>
                    <label class="mb-1 block text-sm font-medium">Título <span class="text-fb-muted">(opcional)</span></label>
                    <input v-model="title" class="gofio-input" maxlength="150" placeholder="Dale un título a tu video…" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Descripción <span class="text-fb-muted">(opcional)</span></label>
                    <textarea v-model="description" class="gofio-input" rows="3" maxlength="500" placeholder="Cuéntanos de qué trata…" />
                </div>

                <!-- Barra de progreso de subida -->
                <div v-if="uploading" class="space-y-1">
                    <div class="flex justify-between text-xs text-fb-muted">
                        <span>Subiendo video…</span>
                        <span>{{ progress }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-fb-border overflow-hidden">
                        <div class="h-full bg-brandColor transition-all duration-300 rounded-full" :style="{ width: progress + '%' }" />
                    </div>
                </div>

                <!-- Mensajes -->
                <p v-if="error" class="rounded bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                    <FaIcon icon="fa-solid fa-circle-exclamation" class="mr-1" />{{ error }}
                </p>
                <p v-if="success" class="rounded bg-green-50 border border-green-200 p-3 text-sm text-green-700">
                    <FaIcon icon="fa-solid fa-circle-check" class="mr-1" />{{ success }}
                </p>

                <!-- Botones -->
                <div class="flex gap-3">
                    <button
                        type="button"
                        class="gofio-btn-primary flex-1"
                        :disabled="!canSubmit"
                        @click="submit"
                    >
                        <FaIcon icon="fa-solid fa-upload" class="mr-1" />
                        {{ uploading ? `Subiendo ${progress}%…` : 'Publicar video' }}
                    </button>
                    <Link href="/vidu" class="gofio-btn-secondary">Cancelar</Link>
                </div>
            </div>
        </div>
    </GofioLayout>
</template>
