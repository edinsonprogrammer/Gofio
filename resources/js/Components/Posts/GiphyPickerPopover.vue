<script setup>
/**
 * Selector de GIFs de GIPHY con búsqueda, tendencias y atribución de marca requerida.
 */

import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useGiphy } from '@/composables/useGiphy';

const emit = defineEmits(['select']);

const { configured, fetchTrending, search } = useGiphy();

const query = ref('');
const gifs = ref([]);
const loading = ref(false);
const error = ref('');
const searchTimer = ref(null);

/** Carga tendencias o resultados de búsqueda según el texto del buscador. */
const loadGifs = async () => {
    if (! configured.value) {
        gifs.value = [];
        error.value = 'GIPHY no está configurado en el servidor.';
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        const trimmed = query.value.trim();
        gifs.value = trimmed
            ? await search(trimmed)
            : await fetchTrending();
    } catch (e) {
        gifs.value = [];
        error.value = e.response?.data?.message || 'No se pudieron cargar los GIFs.';
    } finally {
        loading.value = false;
    }
};

/** Emite el GIF elegido al componente padre. */
const pickGif = (gif) => {
    if (! gif?.url) {
        return;
    }

    emit('select', {
        id: gif.id,
        url: gif.url,
        preview_url: gif.preview_url || gif.url,
        title: gif.title || 'GIF',
    });
};

/** Debounce de búsqueda para no saturar la API. */
watch(query, () => {
    if (searchTimer.value) {
        clearTimeout(searchTimer.value);
    }

    searchTimer.value = setTimeout(() => {
        loadGifs();
    }, 350);
});

onMounted(loadGifs);

onBeforeUnmount(() => {
    if (searchTimer.value) {
        clearTimeout(searchTimer.value);
    }
});
</script>

<template>
    <!-- Buscador y cuadrícula de GIFs con atribución Powered by GIPHY -->
    <div class="giphy-popover">
        <div class="giphy-popover__search-wrap">
            <input
                v-model="query"
                type="search"
                class="giphy-popover__search"
                placeholder="Buscar en GIPHY..."
                autocomplete="off"
            />
        </div>

        <div class="giphy-popover__body">
            <p v-if="loading" class="giphy-popover__status">Cargando GIFs…</p>
            <p v-else-if="error" class="giphy-popover__status giphy-popover__status--error">{{ error }}</p>
            <p v-else-if="!gifs.length" class="giphy-popover__status">No hay resultados.</p>

            <div v-else class="giphy-popover__grid">
                <button
                    v-for="gif in gifs"
                    :key="gif.id"
                    type="button"
                    class="giphy-popover__item"
                    :title="gif.title"
                    @click="pickGif(gif)"
                >
                    <img
                        :src="gif.preview_url || gif.url"
                        :alt="gif.title || 'GIF'"
                        class="giphy-popover__thumb"
                        loading="lazy"
                    />
                </button>
            </div>
        </div>

        <footer class="giphy-popover__footer">
            <a
                href="https://giphy.com/"
                target="_blank"
                rel="noopener noreferrer"
                class="giphy-popover__brand"
            >
                Powered by GIPHY
            </a>
        </footer>
    </div>
</template>

<style scoped>
.giphy-popover {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    max-height: inherit;
    border-radius: 0.75rem;
    overflow: hidden;
    border: 1px solid var(--color-border);
    background: white;
}

.giphy-popover__search-wrap {
    padding: 0.65rem 0.75rem 0.5rem;
    border-bottom: 1px solid var(--color-border);
    background: #fafafa;
}

.giphy-popover__search {
    width: 100%;
    border-radius: 0.5rem;
    border: 1px solid var(--color-border);
    padding: 0.45rem 0.65rem;
    font-size: 0.8125rem;
    outline: none;
}

.giphy-popover__search:focus {
    border-color: var(--color-brand);
    box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.15);
}

.giphy-popover__body {
    flex: 1;
    min-height: 0;
    overflow: auto;
    padding: 0.5rem;
}

.giphy-popover__status {
    padding: 1rem 0.5rem;
    text-align: center;
    font-size: 0.8125rem;
    color: var(--color-muted, #64748b);
}

.giphy-popover__status--error {
    color: #dc2626;
}

.giphy-popover__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.35rem;
}

.giphy-popover__item {
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 0.45rem;
    border: 0;
    padding: 0;
    background: #f1f5f9;
    cursor: pointer;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}

.giphy-popover__item:hover {
    transform: scale(1.03);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
}

.giphy-popover__thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.giphy-popover__footer {
    padding: 0.45rem 0.75rem;
    border-top: 1px solid var(--color-border);
    background: #fafafa;
    text-align: center;
}

.giphy-popover__brand {
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #111827;
    text-decoration: none;
}

.giphy-popover__brand:hover {
    color: var(--color-brand);
}
</style>
