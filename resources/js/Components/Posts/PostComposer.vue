<script setup>
/**
 * Compositor de publicaciones con editor enriquecido, categoría e imágenes.
 */

import { computed, defineAsyncComponent, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import FaIcon from '@/Components/UI/FaIcon.vue';
import { useGiphy } from '@/composables/useGiphy';
import { plainTextLength, sanitizeRichHtml, execEditorCommand, applyInlineStyle, toggleList, COMPOSER_FONT_FAMILIES, COMPOSER_FONT_SIZES, COMPOSER_PALETTE } from '@/utils/richText';

const EmojiPickerPopover = defineAsyncComponent(() => import('@/Components/Posts/EmojiPickerPopover.vue'));
const GiphyPickerPopover = defineAsyncComponent(() => import('@/Components/Posts/GiphyPickerPopover.vue'));

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['created']);

const page = usePage();
const composer = computed(() => page.props.postComposer ?? null);
const { configured: giphyConfigured, packIcon: giphyPackIcon } = useGiphy();

const CODE_LANGUAGES = [
    { value: 'javascript', label: 'JavaScript' },
    { value: 'typescript', label: 'TypeScript' },
    { value: 'php', label: 'PHP' },
    { value: 'python', label: 'Python' },
    { value: 'bash', label: 'Bash' },
    { value: 'html', label: 'HTML' },
    { value: 'css', label: 'CSS' },
    { value: 'sql', label: 'SQL' },
    { value: 'json', label: 'JSON' },
    { value: 'plaintext', label: 'Texto simple' },
];

const title = ref('');
const categoryId = ref(props.categories[0]?.id ?? '');
const tags = ref('');
const blockComments = ref(false);
const isPrivate = ref(false);
const loading = ref(false);
const error = ref('');
const expanded = ref(false);

const editorEl = ref(null);
const bodyHtml = ref('');

const headings = ref([]);
const newHeading = ref('');
const showHeadingPanel = ref(false);

const codeBlocks = ref([]);
const showCodePanel = ref(false);
const codeForm = ref({ language: 'javascript', code: '' });

const images = ref([]);
const gifs = ref([]);
const imageInputRef = ref(null);
const uploadingImage = ref(false);

const showGiphy = ref(false);
const giphyButtonRef = ref(null);
const giphyPanelRef = ref(null);
const giphyPanelStyle = ref({});

const textColor = ref('#0F172A');
const highlightColor = ref('#FEF08A');
const fontFamily = ref('inherit');
const fontSize = ref('15px');

const showEmoji = ref(false);
const emojiButtonRef = ref(null);
const emojiPanelRef = ref(null);
const emojiPanelStyle = ref({});
const savedRange = ref(null);

const EMOJI_PANEL_WIDTH = 352;
const EMOJI_PANEL_HEIGHT = 420;
const EMOJI_PANEL_GAP = 10;
const GIPHY_PANEL_WIDTH = 352;
const GIPHY_PANEL_HEIGHT = 420;

const canPublish = computed(() => composer.value?.can_create !== false);
const maxTitleLength = computed(() => composer.value?.max_title_length ?? 60);
const maxContentLength = computed(() => composer.value?.max_content_length ?? 5000);
const charCount = computed(() => plainTextLength(bodyHtml.value));
const overLimit = computed(() => charCount.value > maxContentLength.value);
const counterColor = computed(() => {
    if (overLimit.value) return 'text-red-600';
    if (charCount.value > maxContentLength.value * 0.9) return 'text-amber-600';
    return 'text-fb-muted';
});


/** Verifica si el usuario puede usar una herramienta del compositor. */
const canUseTool = (tool) => (composer.value?.tools ?? []).includes(tool);
const maxImages = computed(() => composer.value?.max_images_per_post ?? 0);
const maxGifs = computed(() => composer.value?.max_gifs_per_post ?? 5);
const canShowGiphyButton = computed(() => canUseTool('gif') && giphyConfigured.value);
const canInsertGiphy = computed(() => canShowGiphyButton.value && gifs.value.length < maxGifs.value);

const hasAnyContent = computed(() => (
    charCount.value > 0 || codeBlocks.value.length > 0 || images.value.length > 0 || gifs.value.length > 0 || headings.value.length > 0
));


/** Sincroniza el HTML del editor con el estado reactivo. */
const syncFromEditor = () => {
    bodyHtml.value = editorEl.value?.innerHTML ?? '';
};


/** Guarda el rango seleccionado del editor contenteditable. */
const saveSelection = () => {
    const sel = window.getSelection();
    if (sel && sel.rangeCount > 0 && editorEl.value?.contains(sel.anchorNode)) {
        savedRange.value = sel.getRangeAt(0).cloneRange();
    }
};


/** Restaura el rango de selección guardado en el editor. */
const restoreSelection = () => {
    if (!editorEl.value) {
        return false;
    }

    editorEl.value.focus();
    const sel = window.getSelection();
    sel.removeAllRanges();

    if (savedRange.value) {
        sel.addRange(savedRange.value);
        return true;
    }

    const range = document.createRange();
    range.selectNodeContents(editorEl.value);
    range.collapse(false);
    sel.addRange(range);

    return false;
};


/** Ejecuta un comando de formato básico (negrita, listas, alineación, etc.). */
const format = (command, value = null) => {
    if (!editorEl.value) {
        return;
    }

    restoreSelection();
    execEditorCommand(editorEl.value, command, value);
    syncFromEditor();
    saveSelection();
};

/** Inserta viñetas o numeración capturando la selección activa del editor. */
const applyList = (ordered = false) => {
    if (! editorEl.value) {
        return;
    }

    const selection = window.getSelection();
    if (selection?.rangeCount > 0 && editorEl.value.contains(selection.anchorNode)) {
        savedRange.value = selection.getRangeAt(0).cloneRange();
    }

    restoreSelection();
    toggleList(editorEl.value, ordered);
    syncFromEditor();
    saveSelection();
};

/** Aplica color de texto a la selección. */
const applyTextColor = () => {
    if (!editorEl.value) return;
    restoreSelection();
    execEditorCommand(editorEl.value, 'foreColor', textColor.value);
    syncFromEditor();
    saveSelection();
};

/** Aplica color de resalto a la selección. */
const applyHighlightColor = () => {
    if (!editorEl.value) return;
    restoreSelection();
    execEditorCommand(editorEl.value, 'hiliteColor', highlightColor.value);
    syncFromEditor();
    if (!editorEl.value.innerHTML.includes(highlightColor.value.replace('#', ''))) {
        execEditorCommand(editorEl.value, 'backColor', highlightColor.value);
        syncFromEditor();
    }
    saveSelection();
};

/** Cambia la tipografía (familia) del texto seleccionado. */
const applyFontFamily = () => {
    if (!editorEl.value || fontFamily.value === 'inherit') return;
    restoreSelection();
    applyInlineStyle(editorEl.value, 'font-family', fontFamily.value);
    syncFromEditor();
    saveSelection();
};

/** Cambia el tamaño tipográfico del texto seleccionado. */
const applyFontSize = () => {
    if (!editorEl.value) return;
    restoreSelection();
    applyInlineStyle(editorEl.value, 'font-size', fontSize.value);
    syncFromEditor();
    saveSelection();
};

/** Aplica efectos de texto adicionales. */
const applyTextEffect = (effect) => {
    if (effect === 'uppercase') {
        restoreSelection();
        applyInlineStyle(editorEl.value, 'text-transform', 'uppercase');
        syncFromEditor();
        saveSelection();
        return;
    }

    format(effect);
};


/** Añade un encabezado estructurado al borrador del post. */
const addHeading = () => {
    const text = newHeading.value.trim();
    if (!text) return;
    headings.value.push(text);
    newHeading.value = '';
    showHeadingPanel.value = false;
};


/** Quita un encabezado del borrador. */
const removeHeading = (index) => headings.value.splice(index, 1);


/** Despliega el formulario para insertar bloque de código. */
const openCodePanel = () => {
    showCodePanel.value = true;
    showHeadingPanel.value = false;
};


/** Agrega un bloque de código con lenguaje al contenido. */
const addCodeBlock = () => {
    if (!codeForm.value.code.trim()) return;
    codeBlocks.value.push({ ...codeForm.value });
    codeForm.value = { language: 'javascript', code: '' };
    showCodePanel.value = false;
};


/** Elimina un bloque de código del borrador. */
const removeCodeBlock = (index) => codeBlocks.value.splice(index, 1);


/** Abre el diálogo nativo de selección de imagen. */
const pickImage = () => {
    if (images.value.length >= maxImages.value) {
        error.value = `Tu rango permite máximo ${maxImages.value} imagen(es) por post.`;
        return;
    }
    imageInputRef.value?.click();
};


/** Sube la imagen elegida y la adjunta al borrador del post. */
const onImageSelected = async (event) => {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;

    uploadingImage.value = true;
    error.value = '';

    try {
        const formData = new FormData();
        formData.append('image', file);

        const { data } = await window.axios.post('/api/uploads/post-image', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        images.value.push({ url: data.file.url });
        expanded.value = true;
    } catch (e) {
        error.value = e.response?.data?.message
            || e.response?.data?.errors?.image?.[0]
            || 'No se pudo subir la imagen.';
    } finally {
        uploadingImage.value = false;
    }
};


/** Elimina la imagen adjunta pendiente de publicar. */
const removeImage = (index) => images.value.splice(index, 1);


/** Inserta un GIF de GIPHY en el borrador del post. */
const addGif = (gif) => {
    if (! canInsertGiphy.value) {
        return;
    }

    gifs.value.push({
        url: gif.url,
        giphy_id: gif.id,
        title: gif.title,
        preview_url: gif.preview_url,
    });
    showGiphy.value = false;
    expanded.value = true;
};


/** Elimina un GIF del borrador. */
const removeGif = (index) => gifs.value.splice(index, 1);


/** Posiciona el popover de GIPHY junto al botón. */
const updateGiphyPanelPosition = () => {
    const button = giphyButtonRef.value;
    if (! button) {
        return;
    }

    const rect = button.getBoundingClientRect();
    const viewportW = window.innerWidth;
    const margin = 8;
    const headerClearance = 64;

    let top = rect.top - GIPHY_PANEL_HEIGHT - EMOJI_PANEL_GAP;
    let maxHeight = GIPHY_PANEL_HEIGHT;

    if (top < headerClearance) {
        top = rect.bottom + EMOJI_PANEL_GAP;
        maxHeight = Math.min(GIPHY_PANEL_HEIGHT, window.innerHeight - top - margin);
    } else {
        maxHeight = Math.min(GIPHY_PANEL_HEIGHT, rect.top - headerClearance - EMOJI_PANEL_GAP);
        top = rect.top - maxHeight - EMOJI_PANEL_GAP;
    }

    let left = rect.left;
    if (left + GIPHY_PANEL_WIDTH > viewportW - margin) {
        left = viewportW - GIPHY_PANEL_WIDTH - margin;
    }

    giphyPanelStyle.value = {
        position: 'fixed',
        top: `${Math.max(headerClearance, top)}px`,
        left: `${Math.max(margin, left)}px`,
        width: `${Math.min(GIPHY_PANEL_WIDTH, viewportW - margin * 2)}px`,
        maxHeight: `${Math.max(280, maxHeight)}px`,
        zIndex: 9999,
    };
};


/** Muestra u oculta el selector de GIFs de GIPHY. */
const toggleGiphy = () => {
    if (! canInsertGiphy.value) {
        return;
    }

    showGiphy.value = ! showGiphy.value;
    if (showGiphy.value) {
        showEmoji.value = false;
        nextTick(updateGiphyPanelPosition);
    }
};


/** Inserta el GIF elegido en el borrador. */
const onGiphySelect = (gif) => addGif(gif);


/** Posiciona el popover de emojis junto al botón. */
const updateEmojiPanelPosition = () => {
    const button = emojiButtonRef.value;
    if (!button) {
        return;
    }

    const rect = button.getBoundingClientRect();
    const viewportW = window.innerWidth;
    const viewportH = window.innerHeight;
    const margin = 8;
    const headerClearance = 64;

    let top = rect.top - EMOJI_PANEL_HEIGHT - EMOJI_PANEL_GAP;
    let maxHeight = EMOJI_PANEL_HEIGHT;

    
    if (top < headerClearance) {
        top = rect.bottom + EMOJI_PANEL_GAP;
        maxHeight = Math.min(EMOJI_PANEL_HEIGHT, viewportH - top - margin);
    } else {
        maxHeight = Math.min(EMOJI_PANEL_HEIGHT, rect.top - headerClearance - EMOJI_PANEL_GAP);
        top = rect.top - maxHeight - EMOJI_PANEL_GAP;
    }

    let left = rect.left;
    if (left + EMOJI_PANEL_WIDTH > viewportW - margin) {
        left = viewportW - EMOJI_PANEL_WIDTH - margin;
    }
    left = Math.max(margin, left);

    emojiPanelStyle.value = {
        position: 'fixed',
        top: `${Math.max(headerClearance, top)}px`,
        left: `${left}px`,
        width: `${Math.min(EMOJI_PANEL_WIDTH, viewportW - margin * 2)}px`,
        maxHeight: `${Math.max(280, maxHeight)}px`,
        zIndex: 9999,
    };
};


/** Muestra u oculta el selector de emojis. */
const toggleEmoji = () => {
    saveSelection();
    showEmoji.value = !showEmoji.value;
    if (showEmoji.value) {
        nextTick(updateEmojiPanelPosition);
    }
};


/** Inserta el emoji elegido en el cuerpo del post. */
const onEmojiSelect = (unicode) => {
    restoreSelection();
    document.execCommand('insertText', false, unicode);
    syncFromEditor();
    showEmoji.value = false;
    saveSelection();
};


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


/** Reubica los menús flotantes cuando cambia el tamaño de la ventana. */
const onViewportChange = () => {
    if (showEmoji.value) {
        updateEmojiPanelPosition();
    }

    if (showGiphy.value) {
        updateGiphyPanelPosition();
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

document.addEventListener('mousedown', onDocumentClick);
window.addEventListener('resize', onViewportChange);
window.addEventListener('scroll', onViewportChange, true);

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocumentClick);
    window.removeEventListener('resize', onViewportChange);
    window.removeEventListener('scroll', onViewportChange, true);
});


/** Construye el array de bloques Editor.js a partir del borrador. */
const buildBlocks = () => {
    const blocks = [];

    headings.value.forEach((text) => {
        blocks.push({ type: 'header', data: { text, level: 3 } });
    });

    const paragraphHtml = sanitizeRichHtml(bodyHtml.value);
    if (paragraphHtml) {
        blocks.push({ type: 'paragraph', data: { text: paragraphHtml } });
    }

    codeBlocks.value.forEach((block) => {
        blocks.push({ type: 'code', data: { code: block.code, language: block.language } });
    });

    images.value.forEach((image) => {
        blocks.push({ type: 'image', data: { url: image.url } });
    });

    gifs.value.forEach((gif) => {
        blocks.push({
            type: 'gif',
            data: {
                url: gif.url,
                giphy_id: gif.giphy_id,
                title: gif.title,
                preview_url: gif.preview_url,
            },
        });
    });

    return blocks;
};


/** Limpia todos los campos tras publicar o cancelar. */
const resetForm = () => {
    title.value = '';
    tags.value = '';
    blockComments.value = false;
    isPrivate.value = false;
    headings.value = [];
    codeBlocks.value = [];
    images.value = [];
    gifs.value = [];
    bodyHtml.value = '';
    if (editorEl.value) editorEl.value.innerHTML = '';
    expanded.value = false;
};


/** Envía el formulario al servidor y gestiona errores de validación. */
const submit = async (asDraft = false) => {
    error.value = '';

    if (!canPublish.value) {
        error.value = 'Has alcanzado tu límite diario de publicaciones.';
        return;
    }

    if (!title.value.trim()) {
        error.value = 'El título es obligatorio.';
        return;
    }

    if (!categoryId.value) {
        error.value = 'Selecciona una categoría.';
        return;
    }

    if (!hasAnyContent.value) {
        error.value = 'Escribe algo, añade una imagen, un GIF o un bloque de código antes de publicar.';
        return;
    }

    if (overLimit.value) {
        error.value = `Te pasaste del límite de ${maxContentLength.value} caracteres para tu rango.`;
        return;
    }

    loading.value = true;

    try {
        const payload = {
            title: title.value,
            category_id: categoryId.value,
            content: { blocks: buildBlocks() },
            status: asDraft ? 'draft' : 'published',
        };

        if (composer.value?.options?.tags) {
            payload.tags = tags.value.trim() || null;
        }
        if (composer.value?.options?.block_comments) {
            payload.block_comments = blockComments.value;
        }
        if (composer.value?.options?.private) {
            payload.is_private = isPrivate.value;
        }

        const { data } = await window.axios.post('/api/posts', payload);

        resetForm();
        emit('created', data.data);
    } catch (e) {
        error.value = e.response?.data?.message
            || Object.values(e.response?.data?.errors || {}).flat().join(' ')
            || 'Error al publicar.';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <!-- Formulario de creación de publicación con editor enriquecido -->

    <div class="gofio-box overflow-hidden">
        <div class="gofio-box-header-accent flex flex-wrap items-center justify-between gap-2">
            <span>¿Qué está pasando?</span>
            <span v-if="composer" class="text-xs font-normal opacity-80">
                {{ composer.posts_today ?? 0 }}/{{ composer.max_posts_per_day }} hoy
            </span>
        </div>

        <div class="space-y-3 p-4">
            <input
                v-model="title"
                type="text"
                class="gofio-input font-medium"
                :placeholder="`Título del post (máx. ${maxTitleLength})`"
                :maxlength="maxTitleLength"
                @focus="expanded = true"
            />

            <select v-model="categoryId" class="gofio-input text-sm">
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>

            
            <div class="twt-editor-wrap">
                <div
                    ref="editorEl"
                    class="twt-editor"
                    contenteditable="true"
                    :data-placeholder="composer ? 'Comparte algo con la comunidad...' : 'Inicia sesión para publicar.'"
                    @input="syncFromEditor"
                    @focus="expanded = true; saveSelection()"
                    @mouseup="saveSelection"
                    @keyup="saveSelection"
                ></div>
            </div>

            
            <div v-if="headings.length" class="flex flex-wrap gap-1.5">
                <span
                    v-for="(h, i) in headings"
                    :key="i"
                    class="inline-flex items-center gap-1.5 rounded bg-[#F0FDFA] px-2 py-1 text-xs font-bold text-brandColor ring-1 ring-fb-border"
                >
                    <FaIcon icon="fa-solid fa-heading" class="text-[10px]" />
                    {{ h }}
                    <button type="button" class="text-fb-muted hover:text-red-600" @click="removeHeading(i)">
                        <FaIcon icon="fa-solid fa-xmark" />
                    </button>
                </span>
            </div>

            
            <div v-if="showHeadingPanel" class="flex gap-2 rounded border border-fb-border bg-[#F5F6F7] p-2">
                <input
                    v-model="newHeading"
                    type="text"
                    class="gofio-input text-sm"
                    placeholder="Texto del título"
                    :maxlength="maxTitleLength"
                    @keyup.enter="addHeading"
                />
                <button type="button" class="gofio-btn-primary text-xs" @click="addHeading">Añadir</button>
                <button type="button" class="gofio-btn-secondary text-xs" @click="showHeadingPanel = false">Cancelar</button>
            </div>

            
            <div v-if="showCodePanel" class="space-y-2 rounded border border-fb-border bg-[#0d1117] p-3">
                <div class="flex items-center justify-between gap-2">
                    <select v-model="codeForm.language" class="gofio-input w-40 text-xs">
                        <option v-for="lang in CODE_LANGUAGES" :key="lang.value" :value="lang.value">{{ lang.label }}</option>
                    </select>
                    <span class="text-xs font-semibold text-slate-300">
                        <FaIcon icon="fa-solid fa-code" class="mr-1" />Bloque de código
                    </span>
                </div>
                <textarea
                    v-model="codeForm.code"
                    rows="6"
                    class="w-full rounded border border-slate-700 bg-[#0d1117] p-2 font-mono text-xs text-slate-100 outline-none focus:border-brandColor"
                    placeholder="Pega o escribe tu código aquí..."
                ></textarea>
                <div class="flex gap-2">
                    <button type="button" class="gofio-btn-primary text-xs" @click="addCodeBlock">Insertar código</button>
                    <button type="button" class="gofio-btn-secondary text-xs" @click="showCodePanel = false">Cancelar</button>
                </div>
            </div>

            
            <div v-for="(block, i) in codeBlocks" :key="'code-'+i" class="post-code-block">
                <div class="post-code-block__header">
                    <span><FaIcon icon="fa-solid fa-code" class="mr-1.5" />{{ block.language }}</span>
                    <button type="button" class="text-slate-400 hover:text-red-400" @click="removeCodeBlock(i)">
                        <FaIcon icon="fa-solid fa-trash" />
                    </button>
                </div>
                <pre class="post-code-block__body"><code>{{ block.code }}</code></pre>
            </div>

            
            <div v-if="images.length" class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                <div v-for="(img, i) in images" :key="'img-'+i" class="group relative overflow-hidden rounded border border-fb-border">
                    <img :src="img.url" alt="" class="h-32 w-full object-cover" />
                    <button
                        type="button"
                        class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-xs text-white opacity-0 transition group-hover:opacity-100"
                        @click="removeImage(i)"
                    >
                        <FaIcon icon="fa-solid fa-xmark" />
                    </button>
                </div>
            </div>

            <!-- Vista previa de GIFs de GIPHY seleccionados -->
            <div v-if="gifs.length" class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                <div v-for="(gif, i) in gifs" :key="'gif-'+i" class="group relative overflow-hidden rounded border border-fb-border">
                    <img :src="gif.preview_url || gif.url" :alt="gif.title || 'GIF'" class="h-32 w-full object-cover" />
                    <button
                        type="button"
                        class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-xs text-white opacity-0 transition group-hover:opacity-100"
                        @click="removeGif(i)"
                    >
                        <FaIcon icon="fa-solid fa-xmark" />
                    </button>
                </div>
            </div>

            <input ref="imageInputRef" type="file" accept="image/png,image/jpeg,image/gif,image/webp,image/bmp" class="hidden" @change="onImageSelected" />

            
            <!-- Barra de herramientas de formato enriquecido -->
            <div class="space-y-2 border-t border-fb-border pt-3">
                <div class="flex flex-wrap items-center gap-1">
                    <button type="button" class="composer-tool-btn" title="Negrita" @mousedown.prevent="format('bold')">
                        <FaIcon icon="fa-solid fa-bold" />
                    </button>
                    <button type="button" class="composer-tool-btn" title="Cursiva" @mousedown.prevent="format('italic')">
                        <FaIcon icon="fa-solid fa-italic" />
                    </button>
                    <button type="button" class="composer-tool-btn" title="Subrayado" @mousedown.prevent="format('underline')">
                        <FaIcon icon="fa-solid fa-underline" />
                    </button>
                    <button type="button" class="composer-tool-btn" title="Tachado" @mousedown.prevent="format('strikeThrough')">
                        <FaIcon icon="fa-solid fa-strikethrough" />
                    </button>

                    <span class="mx-1 h-5 w-px bg-fb-border"></span>

                    <label class="composer-select-wrap" title="Tipografía">
                        <FaIcon icon="fa-solid fa-font" class="composer-select-icon" />
                        <select v-model="fontFamily" class="composer-select" @change="applyFontFamily">
                            <option v-for="font in COMPOSER_FONT_FAMILIES" :key="font.value" :value="font.value">
                                {{ font.label }}
                            </option>
                        </select>
                    </label>

                    <label class="composer-select-wrap" title="Tamaño">
                        <FaIcon icon="fa-solid fa-text-height" class="composer-select-icon" />
                        <select v-model="fontSize" class="composer-select" @change="applyFontSize">
                            <option v-for="size in COMPOSER_FONT_SIZES" :key="size.value" :value="size.value">
                                {{ size.label }}
                            </option>
                        </select>
                    </label>

                    <label class="composer-color-wrap" title="Color de letra">
                        <FaIcon icon="fa-solid fa-palette" class="text-[11px] text-fb-muted" />
                        <input v-model="textColor" type="color" class="composer-color-input" @input="applyTextColor" />
                    </label>

                    <label class="composer-color-wrap" title="Color de resalto">
                        <FaIcon icon="fa-solid fa-highlighter" class="text-[11px] text-fb-muted" />
                        <input v-model="highlightColor" type="color" class="composer-color-input" @input="applyHighlightColor" />
                    </label>

                    <span class="mx-1 h-5 w-px bg-fb-border"></span>

                    <button type="button" class="composer-tool-btn" title="Mayúsculas" @mousedown.prevent="applyTextEffect('uppercase')">
                        <FaIcon icon="fa-solid fa-a" />
                    </button>
                    <button type="button" class="composer-tool-btn" title="Superíndice" @mousedown.prevent="format('superscript')">
                        <FaIcon icon="fa-solid fa-superscript" />
                    </button>
                    <button type="button" class="composer-tool-btn" title="Subíndice" @mousedown.prevent="format('subscript')">
                        <FaIcon icon="fa-solid fa-subscript" />
                    </button>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-if="canUseTool('list')">
                            <button type="button" class="composer-tool-btn" title="Viñetas" @mousedown.prevent="applyList(false)">
                                <FaIcon icon="fa-solid fa-list-ul" />
                            </button>
                            <button type="button" class="composer-tool-btn" title="Numeración" @mousedown.prevent="applyList(true)">
                                <FaIcon icon="fa-solid fa-list-ol" />
                            </button>

                            <span class="mx-1 h-5 w-px bg-fb-border"></span>
                        </template>

                        <button type="button" class="composer-tool-btn" title="Alinear a la izquierda" @mousedown.prevent="format('justifyLeft')">
                            <FaIcon icon="fa-solid fa-align-left" />
                        </button>
                        <button type="button" class="composer-tool-btn" title="Centrar" @mousedown.prevent="format('justifyCenter')">
                            <FaIcon icon="fa-solid fa-align-center" />
                        </button>
                        <button type="button" class="composer-tool-btn" title="Alinear a la derecha" @mousedown.prevent="format('justifyRight')">
                            <FaIcon icon="fa-solid fa-align-right" />
                        </button>

                        <span class="mx-1 h-5 w-px bg-fb-border"></span>

                        <button
                            v-if="canUseTool('header')"
                            type="button"
                            class="composer-tool-btn"
                            title="Añadir título"
                            @click="showHeadingPanel = !showHeadingPanel; showCodePanel = false"
                        >
                            <FaIcon icon="fa-solid fa-heading" />
                        </button>
                        <button
                            v-if="canUseTool('code')"
                            type="button"
                            class="composer-tool-btn"
                            title="Insertar bloque de código"
                            @click="openCodePanel"
                        >
                            <FaIcon icon="fa-solid fa-code" />
                        </button>
                        <button
                            v-if="maxImages > 0"
                            type="button"
                            class="composer-tool-btn"
                            title="Añadir imagen"
                            :disabled="uploadingImage"
                            @click="pickImage"
                        >
                            <FaIcon v-if="!uploadingImage" icon="fa-solid fa-image" />
                            <FaIcon v-else icon="fa-solid fa-spinner" class="animate-spin" />
                        </button>
                        <button
                            v-if="canShowGiphyButton"
                            ref="giphyButtonRef"
                            type="button"
                            class="composer-tool-btn"
                            title="Insertar GIF de GIPHY"
                            :disabled="!canInsertGiphy"
                            @mousedown.prevent="toggleGiphy"
                        >
                            <FaIcon :icon="giphyPackIcon" />
                        </button>

                        <div class="relative">
                            <button
                                ref="emojiButtonRef"
                                type="button"
                                class="composer-tool-btn"
                                title="Emoji"
                                @mousedown.prevent="toggleEmoji"
                            >
                                <FaIcon icon="fa-regular fa-face-smile" />
                            </button>
                        </div>
                    </div>

                    <span class="text-xs font-semibold" :class="counterColor">
                        {{ charCount }}/{{ maxContentLength }}
                    </span>
                </div>

                <!-- Paleta rápida de colores -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-fb-muted">Colores rápidos</span>
                    <button
                        v-for="color in COMPOSER_PALETTE"
                        :key="color"
                        type="button"
                        class="composer-swatch"
                        :style="{ background: color, borderColor: color === '#FFFFFF' ? 'var(--color-border)' : color }"
                        :title="`Aplicar ${color}`"
                        @mousedown.prevent="textColor = color; applyTextColor()"
                    />
                </div>
            </div>

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

            <div v-if="composer?.options?.tags || composer?.options?.block_comments || composer?.options?.private" class="flex flex-wrap items-center gap-3 border-t border-fb-border pt-3 text-sm">
                <input
                    v-if="composer?.options?.tags"
                    v-model="tags"
                    type="text"
                    class="gofio-input flex-1 text-xs"
                    placeholder="Etiquetas (separadas por coma)"
                    maxlength="128"
                />
                <label v-if="composer?.options?.block_comments" class="flex items-center gap-1.5 whitespace-nowrap">
                    <input v-model="blockComments" type="checkbox" class="rounded" />
                    Bloquear comentarios
                </label>
                <label v-if="composer?.options?.private" class="flex items-center gap-1.5 whitespace-nowrap">
                    <input v-model="isPrivate" type="checkbox" class="rounded" />
                    Post privado
                </label>
            </div>

            <p v-if="!canPublish" class="text-xs text-amber-700">
                <FaIcon icon="fa-solid fa-triangle-exclamation" class="mr-1" />
                Límite diario alcanzado. Vuelve mañana o sube de rango.
            </p>

            <p v-if="error" class="text-xs text-red-600">{{ error }}</p>

            <div class="flex flex-wrap justify-end gap-2">
                <button
                    v-if="composer?.options?.draft"
                    type="button"
                    class="gofio-btn-secondary"
                    :disabled="loading || !canPublish"
                    @click="submit(true)"
                >
                    Guardar borrador
                </button>
                <button
                    type="button"
                    class="gofio-btn-primary rounded-full px-6"
                    :disabled="loading || !canPublish || overLimit"
                    @click="submit(false)"
                >
                    {{ loading ? 'Publicando...' : 'Publicar' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.twt-editor-wrap {
    border-radius: 0.5rem;
    border: 1px solid var(--color-border);
    background-color: white;
}

.twt-editor {
    min-height: 96px;
    max-height: 320px;
    overflow-y: auto;
    padding: 0.75rem;
    font-size: 0.95rem;
    line-height: 1.5;
    color: var(--text-principal);
    outline: none;
}

.twt-editor:empty::before {
    content: attr(data-placeholder);
    color: var(--color-muted);
    pointer-events: none;
}

.twt-editor :deep(ul),
.twt-editor :deep(ol) {
    margin: 0.35rem 0;
    padding-left: 1.25rem;
    list-style-position: outside;
}

.twt-editor :deep(ul) {
    list-style-type: disc;
}

.twt-editor :deep(ol) {
    list-style-type: decimal;
}

.twt-editor :deep(li) {
    display: list-item;
    margin: 0.15rem 0;
}

.composer-select-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
}

.composer-select-icon {
    position: absolute;
    left: 0.45rem;
    pointer-events: none;
    font-size: 0.65rem;
    color: var(--color-muted);
}

.composer-select {
    height: 2rem;
    min-width: 7.5rem;
    border: 1px solid var(--color-border);
    border-radius: 9999px;
    background: #fff;
    padding: 0 0.65rem 0 1.35rem;
    font-size: 0.7rem;
    color: var(--text-principal);
    outline: none;
}

.composer-select:focus {
    border-color: var(--color-brand);
}

.composer-color-wrap {
    display: inline-flex;
    height: 2rem;
    width: 2rem;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    gap: 0.15rem;
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: 9999px;
    background: #fff;
}

.composer-color-input {
    height: 1.35rem;
    width: 1.35rem;
    cursor: pointer;
    border: 0;
    padding: 0;
    background: transparent;
}

.composer-swatch {
    height: 1.1rem;
    width: 1.1rem;
    border-radius: 9999px;
    border: 1px solid transparent;
}

.composer-tool-btn {
    display: flex;
    height: 2rem;
    width: 2rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    color: var(--color-brand);
    font-size: 0.9rem;
    transition: background-color 0.15s;
}

.composer-tool-btn:hover:not(:disabled) {
    background-color: var(--color-panel);
}

.composer-tool-btn:disabled {
    opacity: 0.5;
}

.post-code-block {
    overflow: hidden;
    border-radius: 0.5rem;
    background-color: #0d1117;
}

.post-code-block__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.4rem 0.75rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: #94a3b8;
    background-color: #161b22;
    border-bottom: 1px solid #21262d;
}

.post-code-block__body {
    margin: 0;
    max-height: 260px;
    overflow: auto;
    padding: 0.75rem;
    font-size: 0.75rem;
    color: #e6edf3;
}

.emoji-floating-panel {
    overflow: hidden;
    border-radius: 0.75rem;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.22);
}
</style>
