<script setup>
/**
 * Formulario de comentario con texto enriquecido y envío por AJAX.
 */

import { computed, defineAsyncComponent, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import { useGiphy } from '@/composables/useGiphy';

const EmojiPickerPopover = defineAsyncComponent(() => import('@/Components/Posts/EmojiPickerPopover.vue'));
const GiphyPickerPopover = defineAsyncComponent(() => import('@/Components/Posts/GiphyPickerPopover.vue'));

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    imageUrl: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Escribe un comentario...',
    },
    submitLabel: {
        type: String,
        default: 'Enviar',
    },
    compact: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    commentsToday: {
        type: Number,
        default: null,
    },
    maxCommentsPerDay: {
        type: Number,
        default: null,
    },
    canCreate: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue', 'update:imageUrl', 'submit']);

const { configured: giphyConfigured, packIcon: giphyPackIcon } = useGiphy();

const textareaRef = ref(null);
const imageInputRef = ref(null);
const emojiButtonRef = ref(null);
const emojiPanelRef = ref(null);
const giphyButtonRef = ref(null);
const giphyPanelRef = ref(null);
const showEmoji = ref(false);
const showGiphy = ref(false);
const uploading = ref(false);
const emojiPanelStyle = ref({});
const giphyPanelStyle = ref({});
const savedRange = ref(null);


/** Indica si el usuario alcanzó la cuota diaria de comentarios. */
const limitReached = computed(() => props.canCreate === false);

/** Muestra el contador diario cuando el padre proporciona los límites. */
const showDailyLimit = computed(() => (
    props.commentsToday !== null && props.maxCommentsPerDay !== null
));

/** Deshabilita el envío por estado externo o por límite diario agotado. */
const submitDisabled = computed(() => props.disabled || limitReached.value);

/** Sincroniza el texto del textarea con el modelo del componente padre. */
const update = (value) => emit('update:modelValue', value);


/** Guarda el rango seleccionado del editor contenteditable. */
const saveSelection = () => {
    const sel = window.getSelection();
    if (sel && sel.rangeCount > 0 && textareaRef.value?.contains(sel.anchorNode)) {
        savedRange.value = sel.getRangeAt(0).cloneRange();
        return;
    }

    if (textareaRef.value && document.activeElement === textareaRef.value) {
        savedRange.value = {
            start: textareaRef.value.selectionStart,
            end: textareaRef.value.selectionEnd,
        };
    }
};


/** Inserta texto o emoji en la posición del cursor. */
const insertAtCursor = (text) => {
    const el = textareaRef.value;
    if (!el) {
        return;
    }

    const start = typeof savedRange.value?.start === 'number'
        ? savedRange.value.start
        : el.selectionStart;
    const end = typeof savedRange.value?.end === 'number'
        ? savedRange.value.end
        : el.selectionEnd;

    const value = props.modelValue;
    const next = value.slice(0, start) + text + value.slice(end);
    update(next);

    nextTick(() => {
        el.focus();
        const pos = start + text.length;
        el.setSelectionRange(pos, pos);
    });
};


/** Posiciona el popover de GIPHY junto al botón. */
const updateGiphyPanelPosition = () => {
    const button = giphyButtonRef.value;
    if (! button) {
        return;
    }

    const rect = button.getBoundingClientRect();
    const width = 320;
    const height = 380;
    const margin = 8;

    let top = rect.top - height - 8;
    if (top < 64) {
        top = rect.bottom + 8;
    }

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


/** Muestra u oculta el selector de GIFs de GIPHY. */
const toggleGiphy = () => {
    if (! giphyConfigured.value) {
        return;
    }

    saveSelection();
    showGiphy.value = ! showGiphy.value;
    if (showGiphy.value) {
        showEmoji.value = false;
        nextTick(updateGiphyPanelPosition);
    }
};


/** Adjunta un GIF de GIPHY al comentario. */
const onGiphySelect = (gif) => {
    emit('update:imageUrl', gif.url);
    showGiphy.value = false;
};


/** Posiciona el popover de emojis junto al botón. */
const updateEmojiPanelPosition = () => {
    const button = emojiButtonRef.value;
    if (!button) {
        return;
    }

    const rect = button.getBoundingClientRect();
    const width = 320;
    const height = 380;
    const margin = 8;

    let top = rect.top - height - 8;
    if (top < 64) {
        top = rect.bottom + 8;
    }

    let left = rect.left;
    if (left + width > window.innerWidth - margin) {
        left = window.innerWidth - width - margin;
    }

    emojiPanelStyle.value = {
        position: 'fixed',
        top: `${Math.max(64, top)}px`,
        left: `${Math.max(margin, left)}px`,
        width: `${width}px`,
        maxHeight: `${height}px`,
        zIndex: 9999,
    };
};


/** Muestra u oculta el selector de emojis. */
const toggleEmoji = () => {
    saveSelection();
    showEmoji.value = !showEmoji.value;
    if (showEmoji.value) {
        showGiphy.value = false;
        nextTick(updateEmojiPanelPosition);
    }
};


/** Inserta el emoji elegido en el contenido del comentario. */
const onEmojiSelect = (unicode) => {
    insertAtCursor(unicode);
    showEmoji.value = false;
};


/** Abre el diálogo nativo de selección de imagen. */
const pickImage = () => imageInputRef.value?.click();


/** Sube la imagen elegida y la adjunta al comentario. */
const onImageSelected = async (event) => {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) {
        return;
    }

    uploading.value = true;

    try {
        const formData = new FormData();
        formData.append('image', file);

        const { data } = await window.axios.post('/api/uploads/comment-image', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        emit('update:imageUrl', data.file.url);
    } catch (e) {
        alert(e.response?.data?.errors?.image?.[0] || 'No se pudo subir la imagen.');
    } finally {
        uploading.value = false;
    }
};


/** Elimina la imagen adjunta pendiente de publicar. */
const removeImage = () => emit('update:imageUrl', '');


/** Envía el formulario al servidor y gestiona errores de validación. */
const submit = () => emit('submit');


/** Cierra paneles al hacer clic fuera del componente. */
const onDocumentClick = (event) => {
    const inEmojiButton = emojiButtonRef.value?.contains(event.target);
    const inEmojiPanel = emojiPanelRef.value?.contains(event.target);
    const inGiphyButton = giphyButtonRef.value?.contains(event.target);
    const inGiphyPanel = giphyPanelRef.value?.contains(event.target);

    if (showEmoji.value && ! inEmojiButton && ! inEmojiPanel) {
        showEmoji.value = false;
    }

    if (showGiphy.value && ! inGiphyButton && ! inGiphyPanel) {
        showGiphy.value = false;
    }
};

watch(showEmoji, (open) => {
    if (open) {
        showGiphy.value = false;
        nextTick(updateEmojiPanelPosition);
    }
});

watch(showGiphy, (open) => {
    if (open) {
        showEmoji.value = false;
        nextTick(updateGiphyPanelPosition);
    }
});

/** Reubica popovers flotantes al redimensionar la ventana. */
const onViewportChange = () => {
    updateEmojiPanelPosition();
    updateGiphyPanelPosition();
};

document.addEventListener('mousedown', onDocumentClick);
window.addEventListener('resize', onViewportChange);

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocumentClick);
    window.removeEventListener('resize', onViewportChange);
});
</script>

<template>
    <!-- Formulario de composición de comentario con adjuntos -->

    <div class="comment-composer" :class="{ 'comment-composer--compact': compact }">
        <textarea
            ref="textareaRef"
            :value="modelValue"
            class="gofio-input min-h-[60px] resize-none"
            :class="{ 'min-h-[50px] text-sm': compact }"
            :placeholder="placeholder"
            :disabled="submitDisabled"
            @input="update($event.target.value)"
            @focus="saveSelection"
            @keyup="saveSelection"
            @mouseup="saveSelection"
        />

        <div v-if="imageUrl" class="comment-composer__preview">
            <img :src="imageUrl" alt="Vista previa" class="comment-composer__preview-img" />
            <button type="button" class="comment-composer__preview-remove" @click="removeImage">
                <FaIcon icon="fa-solid fa-xmark" />
            </button>
        </div>

        <div class="comment-composer__toolbar">
            <div class="flex items-center gap-1">
                <button
                    ref="emojiButtonRef"
                    type="button"
                    class="composer-tool-btn"
                    title="Emoji"
                    @mousedown.prevent="toggleEmoji"
                >
                    <FaIcon icon="fa-regular fa-face-smile" />
                </button>
                <button
                    type="button"
                    class="composer-tool-btn"
                    title="Adjuntar imagen"
                    :disabled="uploading || submitDisabled"
                    @click="pickImage"
                >
                    <FaIcon v-if="!uploading" icon="fa-solid fa-image" />
                    <FaIcon v-else icon="fa-solid fa-spinner" class="animate-spin" />
                </button>
                <button
                    v-if="giphyConfigured"
                    ref="giphyButtonRef"
                    type="button"
                    class="composer-tool-btn"
                    title="Insertar GIF de GIPHY"
                    :disabled="submitDisabled"
                    @mousedown.prevent="toggleGiphy"
                >
                    <FaIcon :icon="giphyPackIcon" />
                </button>
            </div>

            <div class="flex items-center gap-2">
                <span v-if="showDailyLimit" class="text-xs text-fb-muted">
                    {{ commentsToday }}/{{ maxCommentsPerDay }} hoy
                </span>
                <button
                    type="button"
                    class="gofio-btn-primary"
                    :class="{ 'text-xs': compact }"
                    :disabled="submitDisabled || uploading"
                    @click="submit"
                >
                    {{ submitLabel }}
                </button>
            </div>
        </div>

        <p v-if="limitReached" class="mt-2 text-xs text-amber-700">
            Has alcanzado tu límite diario de comentarios. Vuelve mañana o sube de rango.
        </p>

        <input ref="imageInputRef" type="file" accept="image/png,image/jpeg,image/gif,image/webp,image/bmp" class="hidden" @change="onImageSelected" />

    <!-- Modal superpuesto -->
        <Teleport to="body">
            <div
                v-if="showEmoji"
                ref="emojiPanelRef"
                class="emoji-floating-panel"
                :style="emojiPanelStyle"
                @mousedown.prevent
            >
                <EmojiPickerPopover @select="onEmojiSelect" />
            </div>
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

<style scoped>
.comment-composer__toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 0.5rem;
    gap: 0.5rem;
}

.comment-composer__preview {
    position: relative;
    margin-top: 0.5rem;
    display: inline-block;
}

.comment-composer__preview-img {
    max-height: 8rem;
    border-radius: 0.5rem;
    border: 1px solid var(--color-border);
}

.comment-composer__preview-remove {
    position: absolute;
    right: 0.25rem;
    top: 0.25rem;
    display: flex;
    height: 1.5rem;
    width: 1.5rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: rgba(0, 0, 0, 0.55);
    color: white;
    font-size: 0.65rem;
}

.comment-composer--compact .comment-composer__preview-img {
    max-height: 6rem;
}
</style>
