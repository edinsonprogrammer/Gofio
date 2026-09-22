<script setup>
/**
 * Selector de emojis basado en emoji-mart con interfaz traducida al español,
 * usado en compositor de posts y comentarios.
 */

import { onMounted, ref } from 'vue';
import data from 'emoji-mart-vue-fast/data/all.json';
import { EmojiIndex, Picker } from 'emoji-mart-vue-fast/src';
import 'emoji-mart-vue-fast/css/emoji-mart.css';

const emit = defineEmits(['select']);

const ready = ref(false);
const emojiIndex = ref(null);

onMounted(() => {
    emojiIndex.value = new EmojiIndex(data);
    ready.value = true;
});

/** Emite el carácter emoji nativo seleccionado al componente padre. */
const onSelect = (emoji) => {
    if (emoji?.native) {
        emit('select', emoji.native);
    }
};
</script>

<template>
    <!-- Picker de emojis con búsqueda y categorías en español -->
    <div v-if="ready" class="emoji-popover">
        <Picker
            :data="emojiIndex"
            set="facebook"
            title="Elige un emoji"
            emoji="point_up"
            :show-preview="true"
            :show-search="true"
            :show-skin-tones="true"
            :i18n="{
                search: 'Buscar',
                clear: 'Limpiar',
                notfound: 'No se encontró',
                skintext: 'Elige tono de piel',
                categories: {
                    search: 'Resultados',
                    recent: 'Recientes',
                    smileys: 'Emoticonos',
                    people: 'Personas',
                    nature: 'Naturaleza',
                    foods: 'Comida',
                    activity: 'Actividades',
                    places: 'Lugares',
                    objects: 'Objetos',
                    symbols: 'Símbolos',
                    flags: 'Banderas',
                    custom: 'Personalizados',
                },
            }"
            @select="onSelect"
        />
    </div>
</template>

<style scoped>
.emoji-popover {
    width: 100%;
    max-width: 100%;
    height: 100%;
    max-height: inherit;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: none;
    border: 1px solid var(--color-border);
    background: white;
}

.emoji-popover :deep(.emoji-mart) {
    width: 100% !important;
    height: 100% !important;
    max-height: inherit !important;
    border: none;
    font-family: inherit;
}

.emoji-popover :deep(.emoji-mart-bar) {
    border-color: var(--color-border);
}

.emoji-popover :deep(.emoji-mart-search input) {
    border-color: var(--color-input-border);
    border-radius: 0.375rem;
}

.emoji-popover :deep(.emoji-mart-anchor-selected) {
    color: var(--color-brand) !important;
}

.emoji-popover :deep(.emoji-mart-anchor-bar) {
    background-color: var(--color-brand) !important;
}
</style>
